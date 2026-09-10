<?php

use Flux\Flux;
use Livewire\Component;

new class extends Component
{
    public function mount(): void
    {
        $membershipType = session()->pull('membership_activated_successfully');

        if (! $membershipType) {
            return;
        }

        $user = auth()->user();

        if ($user->memberProfile) {
            Flux::toast(
                text: 'Your IRDI membership is now active. Welcome to the International Responsible Detectorist Institute!',
                heading: $membershipType === 'charter'
                    ? 'Welcome, Charter Member!'
                    : 'Welcome to IRDI!',
                duration: 10000,
                variant: 'success',
                action: [
                    'label' => 'View My Profile',
                    'href' => route('member-profiles.show', [
                        'profile' => $user->memberProfile->username,
                    ]),
                    'navigate' => true,
                ],
            );

            return;
        }

        Flux::toast(
            text: 'Your IRDI membership is now active. Your next step is to create your public IRDI member profile.',
            heading: $membershipType === 'charter'
                ? 'Welcome, Charter Member!'
                : 'Welcome to IRDI!',
            duration: 10000,
            variant: 'success',
            action: [
                'label' => 'Create My Profile',
                'href' => route('member-profiles.create'),
                'navigate' => true,
            ],
        );
    }
};
?>

<div></div>
