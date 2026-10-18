<?php

namespace App\Http\Controllers;

use App\Models\MemberProfile;
use Illuminate\Contracts\View\View;
use tbQuar\Facades\Quar;

class LandownerIntroductionController
{
    public function __invoke(MemberProfile $profile): View
    {
        $profile->load('user');

        abort_unless($profile->user->membership_status === 'active', 404);
        abort_unless(
            $profile->directory_visible || $profile->personal_information_hidden || $profile->user_id === auth()->id(),
            404
        );

        return view('member-profiles.landowner-introduction', [
            'profile' => $profile,
            'qrCode' => Quar::size(180)->generate(route('member-profiles.show', $profile)),
        ]);
    }
}
