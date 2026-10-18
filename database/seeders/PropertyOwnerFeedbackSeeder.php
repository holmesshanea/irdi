<?php

namespace Database\Seeders;

use App\Models\MemberProfile;
use App\Models\PropertyReview;
use App\Models\PropertyReviewInvitation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PropertyOwnerFeedbackSeeder extends Seeder
{
    public function run(): void
    {
        if (App::environment('production')) {
            throw new RuntimeException('Demo feedback seeding is disabled in production.');
        }

        $comments = [
            'Asked permission first, explained the process clearly, and left the grounds in excellent condition.',
            'Very respectful of our property and careful to fill every hole before leaving.',
            'Friendly, punctual, and considerate. We would happily welcome this detectorist back.',
            'Communicated well throughout the visit and showed us the interesting items recovered.',
            'Took great care around the garden and fences. A positive experience overall.',
            'Good experience, although communication about the visit could have been clearer.',
            'The property was left tidy, but the visit took longer than we had expected.',
            'Generally courteous, with some room for improvement in coordinating access.',
            'We had concerns about how a few areas were restored after detecting.',
            'Would have appreciated better communication and more attention to ground restoration.',
        ];

        $created = 0;
        MemberProfile::query()->whereHas('user', fn ($q) => $q->where('email', 'like', 'irdi-test-%@example.invalid'))
            ->orderBy('id')->chunkById(200, function ($profiles) use ($comments, &$created): void {
                DB::transaction(function () use ($profiles, $comments, &$created): void {
                    foreach ($profiles as $profile) {
                        $target = random_int(5, 15);
                        $existingDemo = PropertyReview::query()->where('member_profile_id', $profile->id)
                            ->where('reviewer_email', 'like', 'irdi-feedback-%@example.invalid')->count();

                        for ($i = $existingDemo + 1; $i <= $target; $i++) {
                            $email = sprintf('irdi-feedback-%d-%02d@example.invalid', $profile->id, $i);
                            if (PropertyReview::query()->where('member_profile_id', $profile->id)
                                ->where('reviewer_email', $email)->exists()) {
                                continue;
                            }

                            $date = now()->subDays(random_int(1, 730));
                            $rating = random_int(1, 100) <= 75 ? random_int(4, 5) : random_int(2, 3);
                            $satisfied = $rating >= 3;
                            $invitation = PropertyReviewInvitation::query()->create([
                                'member_profile_id' => $profile->id,
                                'token' => Str::random(64),
                                'reviewer_email' => $email,
                                'expires_at' => $date->copy()->addDays(30),
                                'email_verified_at' => $date,
                                'used_at' => $date,
                            ]);
                            $invitation->timestamps = false;
                            $invitation->forceFill(['created_at' => $date, 'updated_at' => $date])->save();

                            $review = PropertyReview::query()->create([
                                'member_profile_id' => $profile->id,
                                'property_review_invitation_id' => $invitation->id,
                                'reviewer_email' => $email,
                                'overall_experience' => $rating,
                                'respect_for_property' => max(1, min(5, $rating + random_int(-1, 1))),
                                'communication_courtesy' => max(1, min(5, $rating + random_int(-1, 1))),
                                'care_of_property' => max(1, min(5, $rating + random_int(-1, 1))),
                                'property_left_satisfactorily' => $satisfied,
                                'would_allow_return' => $rating >= 4,
                                'comments' => $comments[$rating >= 4 ? random_int(0, 4) : random_int(5, 9)],
                                'private_concern' => false,
                                'private_concern_details' => null,
                            ]);
                            $review->timestamps = false;
                            $review->forceFill(['created_at' => $date, 'updated_at' => $date])->save();
                            $created++;
                        }
                    }
                });
            });

        $this->command->info("Created {$created} fictional property owner feedback reviews.");
    }
}
