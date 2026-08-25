<?php

namespace App\Http\Controllers;

use App\Mail\PropertyReviewInvitationMail;
use App\Models\MemberProfile;
use App\Models\PropertyReview;
use App\Models\PropertyReviewInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PropertyReviewInvitationController extends Controller
{
    public function store(
        Request $request,
        MemberProfile $profile
    ): RedirectResponse {
        if ($profile->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'reviewer_email' => [
                'required',
                'email',
                'max:255',
            ],
        ]);

        $reviewerEmail = strtolower(
            trim($validated['reviewer_email'])
        );

        $alreadyReviewed = PropertyReview::query()
            ->where('member_profile_id', $profile->id)
            ->where('reviewer_email', $reviewerEmail)
            ->exists();

        if ($alreadyReviewed) {
            return back()->withErrors([
                'reviewer_email_'.$profile->id => 'This email address has already submitted feedback for this IRDI member.',
            ]);
        }

        $invitation = PropertyReviewInvitation::create([
            'member_profile_id' => $profile->id,
            'token' => Str::random(64),
            'reviewer_email' => $reviewerEmail,
            'expires_at' => now()->addDays(30),
        ]);

        $reviewUrl = route('property-reviews.show', [
            'token' => $invitation->token,
        ]);

        Mail::to($reviewerEmail)->send(
            new PropertyReviewInvitationMail(
                profile: $profile,
                reviewUrl: $reviewUrl,
                expiresAt: $invitation->expires_at->format('F j, Y'),
            )
        );

        return back()->with(
            'status',
            'Property owner feedback invitation sent to '
            .$reviewerEmail
            .'.'
        );
    }

    public function resend(
        PropertyReviewInvitation $invitation
    ): RedirectResponse {
        $profile = $invitation->memberProfile;

        if ($profile->user_id !== auth()->id()) {
            abort(403);
        }

        if ($invitation->isUsed()) {
            return back()->with(
                'status',
                'This invitation has already been completed and cannot be resent.'
            );
        }

        if ($invitation->isCancelled()) {
            return back()->with(
                'status',
                'This invitation has been cancelled and cannot be resent.'
            );
        }

        $invitation->update([
            'token' => Str::random(64),
            'verification_code_hash' => null,
            'verification_expires_at' => null,
            'email_verified_at' => null,
            'expires_at' => now()->addDays(30),
        ]);

        $reviewUrl = route('property-reviews.show', [
            'token' => $invitation->token,
        ]);

        Mail::to($invitation->reviewer_email)->send(
            new PropertyReviewInvitationMail(
                profile: $profile,
                reviewUrl: $reviewUrl,
                expiresAt: $invitation->expires_at->format('F j, Y'),
            )
        );

        return back()->with(
            'status',
            'Property owner feedback invitation resent to '
            .$invitation->reviewer_email
            .'.'
        );
    }

    public function cancel(
        PropertyReviewInvitation $invitation
    ): RedirectResponse {
        $profile = $invitation->memberProfile;

        if ($profile->user_id !== auth()->id()) {
            abort(403);
        }

        if ($invitation->isUsed()) {
            return back()->with(
                'status',
                'This invitation has already been completed and cannot be cancelled.'
            );
        }

        if ($invitation->isCancelled()) {
            return back()->with(
                'status',
                'This invitation has already been cancelled.'
            );
        }

        $invitation->update([
            'cancelled_at' => now(),
            'verification_code_hash' => null,
            'verification_expires_at' => null,
            'email_verified_at' => null,
        ]);

        return back()->with(
            'status',
            'Property owner feedback invitation cancelled.'
        );
    }
}
