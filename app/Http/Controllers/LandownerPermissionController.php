<?php

namespace App\Http\Controllers;

use App\Mail\LandownerPermissionNotice;
use App\Models\LandownerPermission;
use App\Models\MemberProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LandownerPermissionController extends Controller
{
    public function index(Request $request): View
    {
        $profiles = MemberProfile::query()->where('user_id', $request->user()->id)->pluck('id');

        return view('pages.permissions.index', [
            'profiles' => MemberProfile::query()->whereIn('id', $profiles)->get(),
            'permissions' => LandownerPermission::query()->whereIn('member_profile_id', $profiles)->latest()->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'member_profile_id' => ['required', 'integer', 'exists:member_profiles,id'],
            'landowner_email' => ['required', 'email', 'max:255'],
            'property_label' => ['required', 'string', 'max:150'],
            'proposed_activity' => ['required', 'string', 'max:3000'],
            'requested_conditions' => ['nullable', 'string', 'max:3000'],
            'starts_on' => ['required', 'date', 'after_or_equal:today'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
        ]);
        $profileIsEligible = MemberProfile::query()
            ->whereKey($data['member_profile_id'])
            ->where('user_id', $request->user()->id)
            ->where('personal_information_hidden', false)
            ->exists();
        abort_unless($profileIsEligible && $request->user()->isInGoodStanding(), 403);
        $key = 'landowner-permission-create:'.$request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['landowner_email' => 'Too many invitations. Please try again later.']);
        }
        $token = Str::random(64);
        $data['landowner_email'] = strtolower(trim($data['landowner_email']));
        $permission = new LandownerPermission($data);
        $permission->forceFill([
            'access_token_hash' => hash('sha256', $token),
            'response_link_expires_at' => now()->addDays(30),
        ]);
        $permission->save();
        $url = route('landowner-permissions.respond', ['token' => $token]);
        try {
            Mail::raw("A detectorist has requested your permission to detect on property you control. Review the proposed dates, conditions, and respond here: {$url}\n\nIRDI does not grant access to private property. You may decline or withdraw permission.", function ($message) use ($data): void {
                $message->to($data['landowner_email'])->subject('IRDI: Review a landowner permission request');
            });
        } catch (\Throwable $e) {
            $permission->delete();
            throw $e;
        }
        RateLimiter::hit($key, 3600);

        return redirect()->route('account.permissions.index')->with('status', 'Permission request sent.');
    }

    private function byToken(string $token): LandownerPermission
    {
        abort_unless(strlen($token) === 64 && ctype_alnum($token), 404);

        return LandownerPermission::query()->with('memberProfile.user')->where('access_token_hash', hash('sha256', $token))->firstOrFail();
    }

    public function respond(string $token): View
    {
        return view('pages.permissions.respond', ['permission' => $this->byToken($token), 'token' => $token]);
    }

    public function decision(Request $request, string $token): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:approved,declined,withdrawn'], 'landowner_conditions' => ['nullable', 'string', 'max:3000'], 'authority_confirmed' => ['accepted_if:decision,approved']]);
        $permission = DB::transaction(function () use ($token, $data): LandownerPermission {
            $permission = LandownerPermission::query()->where('access_token_hash', hash('sha256', $token))->lockForUpdate()->firstOrFail();
            if ($data['decision'] === 'withdrawn') {
                abort_unless($permission->status === 'approved' && ! $permission->withdrawn_at, 409);
                $permission->withdrawn_at = now();
            } else {
                abort_unless($permission->status === 'pending'
                    && ! $permission->isResponseLinkExpired()
                    && ! $permission->isRequestDateExpired(), 409);
                $permission->status = $data['decision'];
                $permission->responded_at = now();
                $permission->landowner_conditions = $data['landowner_conditions'] ?? null;
            }
            $permission->save();

            return $permission->load('memberProfile.user');
        });

        // Do not send the private landowner access token to the member.
        $memberEmail = $permission->memberProfile->user?->email;
        if ($memberEmail) {
            try {
                Mail::to($memberEmail)->send(new LandownerPermissionNotice(
                    'IRDI: Landowner permission status updated',
                    'A landowner has updated the status of your permission request for '.$permission->property_label.'. Current status: '.$permission->effectiveStatus().'. Sign in to your IRDI account to view the record and conditions.',
                ));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return back()->with('status', 'Your decision has been recorded.');
    }

    public function show(Request $request, LandownerPermission $permission): View
    {
        abort_unless($permission->memberProfile()->where('user_id', $request->user()->id)->exists(), 403);

        return view('pages.permissions.record', ['permission' => $permission]);
    }
}
