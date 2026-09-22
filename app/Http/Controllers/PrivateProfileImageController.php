<?php

namespace App\Http\Controllers;

use App\Models\MemberProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PrivateProfileImageController extends Controller
{
    public function __invoke(Request $request, MemberProfile $profile): Response
    {
        abort_unless($profile->user_id === $request->user()->id || $request->user()->isModeratorOrAdministrator(), 403);
        abort_unless(filled($profile->encrypted_profile_image), 404);
        $encrypted = Storage::disk((string) config('filesystems.profile_images_disk', 'public'))->get($profile->encrypted_profile_image);
        abort_unless(is_string($encrypted), 404);
        $payload = json_decode(Crypt::decryptString($encrypted), true, 512, JSON_THROW_ON_ERROR);
        $bytes = base64_decode((string) ($payload['bytes'] ?? ''), true);
        abort_unless(is_string($bytes), 404);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp'], true), 404);

        return response($bytes, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
