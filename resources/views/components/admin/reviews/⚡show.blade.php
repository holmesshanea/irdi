<?php

use App\Models\PropertyReview;
use App\Models\PropertyReviewModeration;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('components.layouts.public')]
class extends Component
{
    public PropertyReview $review;

    public string $moderationNote = '';

    public bool $showHideForm = false;

    public string $restoreNote = '';

    public bool $showRestoreForm = false;

    public function mount(PropertyReview $review): void
    {
        $this->review = $review->load([
            'memberProfile',
            'moderations.user',
        ]);
    }

    public function startHidingReview(): void
    {
        $this->moderationNote = '';
        $this->showHideForm = true;

        $this->resetValidation('moderationNote');
    }

    public function cancelHidingReview(): void
    {
        $this->moderationNote = '';
        $this->showHideForm = false;

        $this->resetValidation('moderationNote');
    }

    public function startRestoringReview(): void
    {
        $this->restoreNote = '';
        $this->showRestoreForm = true;

        $this->resetValidation('restoreNote');
    }

    public function cancelRestoringReview(): void
    {
        $this->restoreNote = '';
        $this->showRestoreForm = false;

        $this->resetValidation('restoreNote');
    }

    public function hideReview(): void
    {
        $this->validate([
            'moderationNote' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'moderationNote.required' => 'Please enter a reason for hiding this review.',
            'moderationNote.min' => 'The moderation note must be at least 10 characters.',
            'moderationNote.max' => 'The moderation note must be 1,000 characters or fewer.',
        ]);

        $this->review->hidden_at = now();
        $this->review->moderation_note = $this->moderationNote;
        $this->review->save();

        PropertyReviewModeration::create([
            'property_review_id' => $this->review->id,
            'user_id' => auth()->id(),
            'action' => 'hidden',
            'note' => $this->moderationNote,
        ]);

        $this->review->load('moderations.user');

        $this->moderationNote = '';
        $this->showHideForm = false;

        session()->flash('status', 'Review hidden successfully.');
    }

    public function restoreReview(): void
    {
        $this->validate([
            'restoreNote' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'restoreNote.required' => 'Please enter a reason for restoring this review.',
            'restoreNote.min' => 'The restore reason must be at least 10 characters.',
            'restoreNote.max' => 'The restore reason must be 1,000 characters or fewer.',
        ]);

        $this->review->hidden_at = null;
        $this->review->save();

        PropertyReviewModeration::create([
            'property_review_id' => $this->review->id,
            'user_id' => auth()->id(),
            'action' => 'restored',
            'note' => $this->restoreNote,
        ]);

        $this->review->load('moderations.user');

        $this->restoreNote = '';
        $this->showRestoreForm = false;

        session()->flash('status', 'Review restored successfully.');
    }
};
?>

<section class="bg-zinc-50">
    <div class="mx-auto max-w-5xl px-6 py-16 lg:px-8 lg:py-20">

        <div class="mb-8">

            <a
                href="{{ route('admin.reviews.index') }}"
                class="text-sm font-medium text-irdi-green hover:underline"
            >
                ← Back to Review Moderation
            </a>

            <h1 class="mt-4 text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                Property Owner Review
            </h1>

            <div class="mt-3 h-1 w-20 bg-irdi-gold"></div>

        </div>

        @if (session('status'))
            <div class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @php
            $averageRating = round(
                (
                    $review->respect_for_property
                    + $review->communication_courtesy
                    + $review->care_of_property
                ) / 3,
                1
            );
        @endphp

        <flux:card class="p-6 {{ $review->hidden_at ? 'opacity-70' : '' }}">

            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">

                <div>

                    <div class="flex flex-wrap items-center gap-2">

                        <h2 class="text-lg font-semibold text-irdi-green">
                            {{ $review->memberProfile->profile_name }}
                        </h2>

                        <flux:badge color="green">
                            Detectorist
                        </flux:badge>

                        @if ($review->hidden_at)
                            <flux:badge color="red">
                                Hidden
                            </flux:badge>
                        @endif

                    </div>

                    <p class="mt-1 text-sm text-zinc-500">
                        {{ '@' . $review->memberProfile->username }}
                    </p>

                    <p class="mt-2 text-sm text-zinc-500">
                        Submitted {{ $review->created_at->format('F j, Y \a\t g:i A') }}
                    </p>

                    @if ($review->hidden_at)
                        <p class="mt-1 text-xs font-medium text-red-600">
                            Hidden {{ $review->hidden_at->format('F j, Y \a\t g:i A') }}
                        </p>
                    @endif

                </div>

                <div class="text-left sm:text-right">

                    <p class="text-sm font-medium text-zinc-500">
                        Overall Rating
                    </p>

                    <p class="mt-1 text-2xl font-bold text-irdi-green">
                        {{ number_format($averageRating, 1) }}
                        <span class="text-sm font-medium text-zinc-500">
                            / 5
                        </span>
                    </p>

                </div>

            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-3">

                <div class="rounded-lg bg-zinc-50 p-4">

                    <p class="text-sm font-medium text-zinc-500">
                        Respect for Property
                    </p>

                    <p class="mt-2 text-xl font-semibold text-irdi-green">
                        {{ $review->respect_for_property }} / 5
                    </p>

                </div>

                <div class="rounded-lg bg-zinc-50 p-4">

                    <p class="text-sm font-medium text-zinc-500">
                        Communication & Courtesy
                    </p>

                    <p class="mt-2 text-xl font-semibold text-irdi-green">
                        {{ $review->communication_courtesy }} / 5
                    </p>

                </div>

                <div class="rounded-lg bg-zinc-50 p-4">

                    <p class="text-sm font-medium text-zinc-500">
                        Care of the Property
                    </p>

                    <p class="mt-2 text-xl font-semibold text-irdi-green">
                        {{ $review->care_of_property }} / 5
                    </p>

                </div>

            </div>

            <div class="mt-6 border-t border-zinc-200 pt-6">

                <p class="text-sm font-medium text-zinc-500">
                    Would Allow Detectorist to Return
                </p>

                <div class="mt-2">

                    @if ($review->would_allow_return)

                        <flux:badge color="green">
                            Yes
                        </flux:badge>

                    @else

                        <flux:badge color="red">
                            No
                        </flux:badge>

                    @endif

                </div>

            </div>

            @if ($review->comments)

                <div class="mt-6 border-t border-zinc-200 pt-6">

                    <p class="text-sm font-medium text-zinc-500">
                        Reviewer Comments
                    </p>

                    <p class="mt-3 whitespace-pre-line leading-7 text-zinc-700">
                        {{ $review->comments }}
                    </p>

                </div>

            @endif

            <div class="mt-6 border-t border-zinc-200 pt-6">

                <p class="text-sm font-medium text-zinc-500">
                    Reviewer Email
                </p>

                <p class="mt-2 text-sm text-zinc-700">
                    {{ $review->reviewer_email }}
                </p>

                <p class="mt-1 text-xs text-zinc-500">
                    Private administrative information. This email address is never displayed publicly.
                </p>

            </div>

            @if ($review->hidden_at)

                <div class="mt-6 border-t border-zinc-200 pt-6">

                    <p class="text-sm font-medium text-zinc-500">
                        Moderation Information
                    </p>

                    <div class="mt-3 rounded-lg bg-red-50 p-4">

                        <p class="text-sm font-medium text-red-700">
                            Hidden {{ $review->hidden_at->format('F j, Y \a\t g:i A') }}
                        </p>

                        @if ($review->moderation_note)

                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-zinc-700">
                                {{ $review->moderation_note }}
                            </p>

                        @endif

                    </div>

                </div>

            @endif

            @if ($review->moderations->isNotEmpty())

                <div class="mt-6 border-t border-zinc-200 pt-6">

                    <p class="text-sm font-medium text-zinc-500">
                        Moderation History
                    </p>

                    <div class="mt-4 space-y-3">

                        @foreach ($review->moderations->sortByDesc('created_at') as $moderation)

                            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">

                                <div class="flex flex-wrap items-center gap-2">

                                    @if ($moderation->action === 'hidden')

                                        <flux:badge color="red">
                                            Hidden
                                        </flux:badge>

                                    @elseif ($moderation->action === 'restored')

                                        <flux:badge color="green">
                                            Restored
                                        </flux:badge>

                                    @else

                                        <flux:badge>
                                            {{ ucfirst($moderation->action) }}
                                        </flux:badge>

                                    @endif

                                    <span class="text-sm text-zinc-500">
                                        {{ $moderation->created_at->format('F j, Y \a\t g:i A') }}
                                    </span>

                                </div>

                                <p class="mt-2 text-sm text-zinc-600">
                                    By
                                    <span class="font-medium text-zinc-800">
                                        {{ $moderation->user?->name ?? 'Former administrator' }}
                                    </span>
                                </p>

                                @if ($moderation->note)

                                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-zinc-700">
                                        {{ $moderation->note }}
                                    </p>

                                @endif

                            </div>

                        @endforeach

                    </div>

                </div>

            @endif

            @if (! $review->hidden_at && $showHideForm)

                <div class="mt-6 border-t border-zinc-200 pt-6">

                    <flux:textarea
                        wire:model="moderationNote"
                        label="Reason for hiding this review"
                        placeholder="Explain why this review is being hidden..."
                        rows="3"
                    />

                    <div class="mt-4 flex justify-end gap-2">

                        <flux:button
                            type="button"
                            variant="outline"
                            wire:click="cancelHidingReview"
                        >
                            Cancel
                        </flux:button>

                        <flux:button
                            type="button"
                            variant="danger"
                            wire:click="hideReview"
                            wire:confirm="Hide this review? It will no longer count toward the detectorist's public rating."
                        >
                            Confirm Hide
                        </flux:button>

                    </div>

                </div>

            @endif

            @if ($review->hidden_at && $showRestoreForm)

                <div class="mt-6 border-t border-zinc-200 pt-6">

                    <flux:textarea
                        wire:model="restoreNote"
                        label="Reason for restoring this review"
                        placeholder="Explain why this review is being restored..."
                        rows="3"
                    />

                    <div class="mt-4 flex justify-end gap-2">

                        <flux:button
                            type="button"
                            variant="outline"
                            wire:click="cancelRestoringReview"
                        >
                            Cancel
                        </flux:button>

                        <flux:button
                            type="button"
                            variant="primary"
                            wire:click="restoreReview"
                            wire:confirm="Restore this review? It will once again count toward the detectorist's public rating."
                        >
                            Confirm Restore
                        </flux:button>

                    </div>

                </div>

            @endif

            <div class="mt-6 flex justify-end border-t border-zinc-200 pt-6">

                @if ($review->hidden_at && ! $showRestoreForm)

                    <flux:button
                        type="button"
                        variant="primary"
                        wire:click="startRestoringReview"
                    >
                        Restore Review
                    </flux:button>

                @elseif (! $review->hidden_at && ! $showHideForm)

                    <flux:button
                        type="button"
                        variant="danger"
                        wire:click="startHidingReview"
                    >
                        Hide Review
                    </flux:button>

                @endif

            </div>

        </flux:card>

    </div>
</section>
