<?php

namespace App\Support;

use App\Models\MemberProfile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ProfilePrivacy
{
    public function set(MemberProfile $profile, bool $hidden): void
    {
        $disk = Storage::disk((string) config('filesystems.profile_images_disk', 'public'));
        $originalPath = null;
        $originalBytes = null;
        $publishedPath = null;
        $archiveToRemove = null;

        try {
            DB::transaction(function () use ($profile, $hidden, $disk, &$originalPath, &$originalBytes, &$publishedPath, &$archiveToRemove): void {
                $current = MemberProfile::query()->lockForUpdate()->findOrFail($profile->id);
                abort_unless($current->user_id === auth()->id(), 403);

                if ((bool) $current->personal_information_hidden === $hidden) {
                    return;
                }

                if ($hidden && $current->profile_image) {
                    $originalPath = $current->profile_image;
                    $originalBytes = $disk->get($originalPath);

                    if (! is_string($originalBytes)) {
                        throw new RuntimeException('Could not read the profile image for privacy protection.');
                    }

                    // Encrypt before storing: even a publicly served disk exposes only ciphertext.
                    $archive = 'profile-image-archives/'.Str::uuid().'.encrypted';
                    $payload = json_encode([
                        'extension' => pathinfo($originalPath, PATHINFO_EXTENSION),
                        'bytes' => base64_encode($originalBytes),
                    ], JSON_THROW_ON_ERROR);

                    if (! $disk->put($archive, Crypt::encryptString($payload))) {
                        throw new RuntimeException('Could not archive the profile image.');
                    }

                    if (! $disk->delete($originalPath) || $disk->exists($originalPath)) {
                        $disk->delete($archive);
                        throw new RuntimeException('Could not remove the public profile image.');
                    }

                    $current->encrypted_profile_image = $archive;
                    $current->profile_image = null;
                }

                if (! $hidden && $current->encrypted_profile_image) {
                    $encrypted = $disk->get($current->encrypted_profile_image);
                    if (! is_string($encrypted)) {
                        throw new RuntimeException('Could not read the archived profile image.');
                    }
                    $payload = json_decode(Crypt::decryptString($encrypted), true, 512, JSON_THROW_ON_ERROR);
                    $extension = strtolower((string) ($payload['extension'] ?? ''));
                    if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'], true)) {
                        throw new RuntimeException('Unsupported archived image extension.');
                    }
                    $bytes = base64_decode((string) ($payload['bytes'] ?? ''), true);
                    if ($bytes === false) {
                        throw new RuntimeException('Invalid archived profile image.');
                    }
                    $publishedPath = 'profile-images/'.Str::uuid().'.'.$extension;
                    if (! $disk->put($publishedPath, $bytes, ['visibility' => 'public'])) {
                        throw new RuntimeException('Could not restore the profile image.');
                    }
                    $current->profile_image = $publishedPath;
                    $archiveToRemove = $current->encrypted_profile_image;
                    $current->encrypted_profile_image = null;
                }

                $current->personal_information_hidden = $hidden;
                $current->save();

                if ($hidden) {
                    $current->propertyReviewInvitations()->whereNull('used_at')->update([
                        'cancelled_at' => now(),
                        'verification_code_hash' => null,
                        'verification_expires_at' => null,
                        'email_verified_at' => null,
                    ]);
                }

                StaffActivity::record(
                    action: $hidden ? 'personal_information_hidden' : 'personal_information_shown',
                    description: $hidden ? 'Enabled personal-information privacy.' : 'Disabled personal-information privacy.',
                    targetUserId: $current->user_id,
                    subject: $current,
                );
            });
        } catch (\Throwable $exception) {
            // Database rollback does not undo storage writes. Restore the previous public state.
            if (is_string($originalPath) && is_string($originalBytes)) {
                if (! $disk->put($originalPath, $originalBytes, ['visibility' => 'public'])) {
                    throw new RuntimeException('Privacy failed and the original image could not be restored; recover it from the encrypted archive.', 0, $exception);
                }
            }
            if (is_string($publishedPath)) {
                $disk->delete($publishedPath);
            }
            throw $exception;
        }

        if (is_string($archiveToRemove)) {
            $disk->delete($archiveToRemove);
        }
    }
}
