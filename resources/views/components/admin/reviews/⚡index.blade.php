<?php

use App\Models\PropertyReview;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\PropertyReviewModeration;

new
#[Layout('components.layouts.public')]
class extends Component
{
    use WithPagination;

    public string $status = 'all';
    public string $search = '';
    public ?int $reviewBeingHidden = null;
    public string $moderationNote = '';
    public ?int $reviewBeingRestored = null;
    public string $restoreNote = '';

    public function startHidingReview(int $reviewId): void
    {
        $this->reviewBeingHidden = $reviewId;
        $this->moderationNote = '';
    }

    public function startRestoringReview(int $reviewId): void
    {
        $this->reviewBeingRestored = $reviewId;
        $this->restoreNote = '';
    }

    public function hideReview(): void
    {
        $this->validate([
            'reviewBeingHidden' => ['required', 'integer'],
            'moderationNote' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'moderationNote.required' => 'Please enter a reason for hiding this review.',
            'moderationNote.min' => 'The moderation note must be at least 10 characters.',
            'moderationNote.max' => 'The moderation note must be 1,000 characters or fewer.',
        ]);

        $review = PropertyReview::findOrFail($this->reviewBeingHidden);

        $review->hidden_at = now();
        $review->moderation_note = $this->moderationNote;
        $review->save();

        PropertyReviewModeration::create([
            'property_review_id' => $review->id,
            'user_id' => auth()->id(),
            'action' => 'hidden',
            'note' => $this->moderationNote,
        ]);

        $this->reviewBeingHidden = null;
        $this->moderationNote = '';

        session()->flash('status', 'Review hidden successfully.');
    }

    public function restoreReview(): void
    {
        $this->validate([
            'reviewBeingRestored' => ['required', 'integer'],
            'restoreNote' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'restoreNote.required' => 'Please enter a reason for restoring this review.',
            'restoreNote.min' => 'The restore reason must be at least 10 characters.',
            'restoreNote.max' => 'The restore reason must be 1,000 characters or fewer.',
        ]);

        $review = PropertyReview::findOrFail($this->reviewBeingRestored);

        $review->hidden_at = null;
        $review->save();

        PropertyReviewModeration::create([
            'property_review_id' => $review->id,
            'user_id' => auth()->id(),
            'action' => 'restored',
            'note' => $this->restoreNote,
        ]);

        $this->reviewBeingRestored = null;
        $this->restoreNote = '';

        session()->flash('status', 'Review restored successfully.');
    }

    public function filterByStatus(string $status): void
    {
        if (! in_array($status, ['all', 'visible', 'hidden'], true)) {
            return;
        }

        $this->status = $status;
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $allReviewsCount = PropertyReview::count();

        $visibleReviewsCount = PropertyReview::query()
            ->whereNull('hidden_at')
            ->count();

        $hiddenReviewsCount = PropertyReview::query()
            ->whereNotNull('hidden_at')
            ->count();

        $reviews = PropertyReview::query()
            ->with('memberProfile')
            ->latest();

        if ($this->status === 'visible') {
            $reviews->whereNull('hidden_at');
        }

        if ($this->status === 'hidden') {
            $reviews->whereNotNull('hidden_at');
        }

        if ($this->search !== '') {
            $search = '%' . $this->search . '%';

            $reviews->where(function ($query) use ($search) {
                $query
                    ->where('reviewer_email', 'like', $search)
                    ->orWhereHas('memberProfile', function ($query) use ($search) {
                        $query
                            ->where('profile_name', 'like', $search)
                            ->orWhere('username', 'like', $search);
                    });
            });
        }

        return [
            'reviews' => $reviews->paginate(20),
            'allReviewsCount' => $allReviewsCount,
            'visibleReviewsCount' => $visibleReviewsCount,
            'hiddenReviewsCount' => $hiddenReviewsCount,
        ];
    }
};
?>

<section class="bg-zinc-50">
    <div class="mx-auto max-w-5xl px-6 py-16 lg:px-8 lg:py-20">

        <div class="mb-8">

            <h1 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                Review Moderation
            </h1>

            <div class="mt-3 h-1 w-20 bg-irdi-gold"></div>

            <p class="mt-4 text-sm text-zinc-600">
                Review IRDI Property Owner Feedback submissions.
            </p>

        </div>

        <div class="mb-8 grid gap-4 sm:grid-cols-3">

            <button
                type="button"
                wire:click="filterByStatus('all')"
                class="text-left"
            >
                <flux:card class="h-full p-5 transition hover:border-irdi-gold {{ $status === 'all' ? 'ring-2 ring-irdi-gold' : '' }}">
                    <p class="text-sm font-medium text-zinc-500">
                        All Reviews
                    </p>

                    <p class="mt-2 text-3xl font-bold text-irdi-green">
                        {{ number_format($allReviewsCount) }}
                    </p>
                </flux:card>
            </button>

            <button
                type="button"
                wire:click="filterByStatus('visible')"
                class="text-left"
            >
                <flux:card class="h-full p-5 transition hover:border-green-500 {{ $status === 'visible' ? 'ring-2 ring-green-500' : '' }}">
                    <p class="text-sm font-medium text-zinc-500">
                        Visible
                    </p>

                    <p class="mt-2 text-3xl font-bold text-green-700">
                        {{ number_format($visibleReviewsCount) }}
                    </p>
                </flux:card>
            </button>

            <button
                type="button"
                wire:click="filterByStatus('hidden')"
                class="text-left"
            >
                <flux:card class="h-full p-5 transition hover:border-red-500 {{ $status === 'hidden' ? 'ring-2 ring-red-500' : '' }}">
                    <p class="text-sm font-medium text-zinc-500">
                        Hidden
                    </p>

                    <p class="mt-2 text-3xl font-bold text-red-700">
                        {{ number_format($hiddenReviewsCount) }}
                    </p>
                </flux:card>
            </button>

        </div>

        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center">

            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search name, username, or reviewer email..."
                class="w-full sm:max-w-md"
            />

            <flux:select
                wire:model.live="status"
                class="w-full sm:max-w-xs"
            >
                <option value="all">All Reviews</option>
                <option value="visible">Visible Reviews</option>
                <option value="hidden">Hidden Reviews</option>
            </flux:select>

        </div>

        @if (session('status'))
            <div class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($reviews->isEmpty())

            <flux:card class="p-6">

                <p class="text-sm text-zinc-600">
                    No property owner reviews have been submitted yet.
                </p>

            </flux:card>

        @else

            <div class="space-y-6">

                @foreach ($reviews as $review)

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

                    <flux:card class="p-5 {{ $review->hidden_at ? 'opacity-70' : '' }}">

                        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">

                            <div class="min-w-0 flex-1">

                                <div class="flex flex-wrap items-center gap-2">

                                    <h2 class="font-semibold text-irdi-green">
                                        {{ $review->memberProfile->profile_name }}
                                    </h2>

                                    <span class="text-sm text-zinc-500">
                        {{ '@' . $review->memberProfile->username }}
                    </span>

                                    @if ($review->hidden_at)
                                        <flux:badge color="red">
                                            Hidden
                                        </flux:badge>
                                    @else
                                        <flux:badge color="green">
                                            Visible
                                        </flux:badge>
                                    @endif

                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-zinc-500">

                    <span>
                        Submitted {{ $review->created_at->format('M j, Y') }}
                    </span>

                                    <span>
                        Rating:
                        <strong class="font-semibold text-irdi-green">
                            {{ number_format($averageRating, 1) }} / 5
                        </strong>
                    </span>

                                    <span>
                        Would Return:
                        @if ($review->would_allow_return)
                                            <span class="font-medium text-green-700">Yes</span>
                                        @else
                                            <span class="font-medium text-red-700">No</span>
                                        @endif
                    </span>

                                </div>

                                @if ($review->comments)
                                    <p class="mt-3 line-clamp-2 text-sm leading-6 text-zinc-600">
                                        {{ $review->comments }}
                                    </p>
                                @endif

                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-2">

                                <flux:button
                                    :href="route('admin.reviews.show', $review)"
                                    variant="outline"
                                >
                                    View Review
                                </flux:button>

                                @if ($review->hidden_at)

                                    <flux:button
                                        type="button"
                                        variant="primary"
                                        wire:click="startRestoringReview({{ $review->id }})"
                                    >
                                        Restore
                                    </flux:button>

                                @else

                                    <flux:button
                                        type="button"
                                        variant="danger"
                                        wire:click="startHidingReview({{ $review->id }})"
                                    >
                                        Hide
                                    </flux:button>

                                @endif

                            </div>

                            @if ($reviewBeingHidden === $review->id)

                                <div class="mt-5 border-t border-zinc-200 pt-5">

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
                                            wire:click="$set('reviewBeingHidden', null)"
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

                            @if ($reviewBeingRestored === $review->id)

                                <div class="mt-5 border-t border-zinc-200 pt-5">

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
                                            wire:click="$set('reviewBeingRestored', null)"
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

                        </div>

                    </flux:card>

                @endforeach

            </div>

            <div class="mt-8">
                {{ $reviews->links() }}
            </div>

        @endif

    </div>
</section>
