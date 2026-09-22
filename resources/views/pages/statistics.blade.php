<?php

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\Intl\Countries;

new
#[Layout('components.layouts.public')]
#[Title('IRDI Statistics | Global Responsible Detectorist Community')]
class extends Component
{
    private const CHART_COUNTRY_LIMIT = 7;

    /** @var array<int, array{id: string, label: string, value: int, percentage: float, country_code: string}> */
    public array $countryData = [];

    /** @var array<int, array{id: string, label: string, value: int, percentage: float, country_code: string|null}> */
    public array $chartCountryData = [];

    public int $membersWithCountryCount = 0;

    /** @var array<int, array{month: string, members: int}> */
    public array $membershipGrowthData = [];

    public function mount(): void
    {
        $rows = MemberProfile::query()
            ->selectRaw('country_code, COUNT(*) as member_count')
            ->whereHas('user', function (Builder $query): void {
                $query->where('membership_status', 'active');
            })
            ->where('personal_information_hidden', false)
            ->whereNotNull('country_code')
            ->where('country_code', '!=', '')
            ->groupBy('country_code')
            ->get();

        $this->membersWithCountryCount = (int) $rows->sum('member_count');

        $this->countryData = $rows
            ->map(function (MemberProfile $row): array {
                $countryCode = (string) $row->country_code;
                $memberCount = (int) $row->getAttribute('member_count');

                return [
                    'id' => strtolower($countryCode),
                    'label' => Countries::getName($countryCode, app()->getLocale()),
                    'value' => $memberCount,
                    'percentage' => $this->membersWithCountryCount > 0
                        ? round(($memberCount / $this->membersWithCountryCount) * 100, 1)
                        : 0.0,
                    'country_code' => $countryCode,
                ];
            })
            ->sort(function (array $a, array $b): int {
                return $b['value'] <=> $a['value'] ?: strcasecmp($a['label'], $b['label']);
            })
            ->values()
            ->all();

        $this->chartCountryData = $this->buildChartCountryData();
        $this->membershipGrowthData = $this->buildMembershipGrowthData();
    }

    /**
     * Keep the donut readable as IRDI expands internationally. The complete
     * country ranking remains available beside the chart.
     *
     * @return array<int, array{id: string, label: string, value: int, percentage: float, country_code: string|null}>
     */
    private function buildChartCountryData(): array
    {
        if (count($this->countryData) <= self::CHART_COUNTRY_LIMIT) {
            return $this->countryData;
        }

        $topCountries = array_slice($this->countryData, 0, self::CHART_COUNTRY_LIMIT);
        $otherCountries = array_slice($this->countryData, self::CHART_COUNTRY_LIMIT);
        $otherMemberCount = array_sum(array_column($otherCountries, 'value'));

        $topCountries[] = [
            'id' => 'other',
            'label' => __('statistics.countries.other'),
            'value' => $otherMemberCount,
            'percentage' => $this->membersWithCountryCount > 0
                ? round(($otherMemberCount / $this->membersWithCountryCount) * 100, 1)
                : 0.0,
            'country_code' => null,
        ];

        return $topCountries;
    }

    /**
     * Build a cumulative month-by-month history from the date each account
     * first became an IRDI member. Current membership status is intentionally
     * not used here so later enforcement actions do not rewrite past joins.
     *
     * @return array<int, array{month: string, members: int}>
     */
    private function buildMembershipGrowthData(): array
    {
        $memberMonths = User::query()
            ->whereNotNull('member_since')
            ->orderBy('member_since')
            ->pluck('member_since')
            ->map(fn ($date): Carbon => Carbon::parse((string) $date)->startOfMonth());

        if ($memberMonths->isEmpty()) {
            return [];
        }

        $joinsByMonth = $memberMonths->countBy(
            fn (Carbon $date): string => $date->format('Y-m')
        );

        $firstMemberMonth = $memberMonths->first();

        if (! $firstMemberMonth instanceof Carbon) {
            return [];
        }

        $start = $firstMemberMonth->copy()->startOfMonth();
        $end = now()->startOfMonth();
        $cumulative = 0;
        $data = [];

        for ($month = $start->copy(); $month->lte($end); $month->addMonth()) {
            $cumulative += (int) ($joinsByMonth[$month->format('Y-m')] ?? 0);

            $data[] = [
                'month' => $month->copy()->locale(app()->getLocale())->translatedFormat('M Y'),
                'members' => $cumulative,
            ];
        }

        return $data;
    }

    public function getActiveMemberCountProperty(): int
    {
        return User::query()
            ->where('membership_status', 'active')
            ->count();
    }

    public function getCountriesRepresentedCountProperty(): int
    {
        return MemberProfile::query()
            ->whereHas('user', function (Builder $query): void {
                $query->where('membership_status', 'active');
            })
            ->where('personal_information_hidden', false)
            ->whereNotNull('country_code')
            ->where('country_code', '!=', '')
            ->distinct()
            ->count('country_code');
    }

    public function getCharterMemberCountProperty(): int
    {
        return User::query()
            ->where('membership_status', 'active')
            ->where('is_charter_member', true)
            ->count();
    }

    public function getPublicDirectoryMemberCountProperty(): int
    {
        return MemberProfile::query()
            ->publicDirectory()
            ->count();
    }
}
?>

<div>
    {{-- Statistics Hero --}}
    <section class="relative overflow-hidden bg-irdi-green">
        <picture class="absolute inset-0">
            <source
                media="(max-width: 640px)"
                srcset="{{ asset('images/mobile/mobile4.png') }}"
            >

            <img
                src="{{ asset('images/carousel/carousel4.png') }}"
                alt=""
                class="h-full w-full object-cover"
            >
        </picture>

        <div class="absolute inset-0 bg-irdi-green/75"></div>

        <div class="relative mx-auto max-w-7xl px-6 py-20 lg:px-8 lg:py-28">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-semibold uppercase tracking-widest text-irdi-gold-light">
                    {{ __('statistics.hero.organization') }}
                </p>

                <h1 class="mt-4 text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                    {{ __('statistics.hero.heading') }}
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-200">
                    {{ __('statistics.hero.description') }}
                </p>
            </div>
        </div>
    </section>

    {{-- Headline statistics --}}
    <section class="bg-zinc-50">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    {{ __('statistics.overview.heading') }}
                </h2>

                <p class="mt-4 text-lg leading-8 text-zinc-600">
                    {{ __('statistics.overview.description') }}
                </p>
            </div>

            <div class="mx-auto mt-8 max-w-3xl rounded-xl border border-irdi-gold/40 bg-irdi-gold/10 p-5 text-center">
                <p class="text-sm leading-6 text-zinc-700">
                    {{ __('statistics.overview.standing_note') }}
                </p>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
                <flux:card class="group p-7 transition hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-zinc-500">
                                {{ __('statistics.cards.active_members') }}
                            </p>

                            <p class="mt-2 text-4xl font-bold tracking-tight text-irdi-green">
                                {{ number_format($this->activeMemberCount) }}
                            </p>
                        </div>

                        <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-irdi-green/10">
                            <flux:icon.users class="size-6 text-irdi-gold" />
                        </div>
                    </div>

                    <p class="mt-4 text-sm leading-6 text-zinc-600">
                        {{ __('statistics.cards.active_members_description') }}
                    </p>
                </flux:card>

                <flux:card class="group p-7 transition hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-zinc-500">
                                {{ __('statistics.cards.countries_represented') }}
                            </p>

                            <p class="mt-2 text-4xl font-bold tracking-tight text-irdi-green">
                                {{ number_format($this->countriesRepresentedCount) }}
                            </p>
                        </div>

                        <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-irdi-green/10">
                            <flux:icon.globe-alt class="size-6 text-irdi-gold" />
                        </div>
                    </div>

                    <p class="mt-4 text-sm leading-6 text-zinc-600">
                        {{ __('statistics.cards.countries_represented_description') }}
                    </p>
                </flux:card>

                <flux:card class="group p-7 transition hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-zinc-500">
                                {{ __('statistics.cards.charter_members') }}
                            </p>

                            <p class="mt-2 text-4xl font-bold tracking-tight text-irdi-green">
                                {{ number_format($this->charterMemberCount) }}
                            </p>
                        </div>

                        <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-irdi-green/10">
                            <flux:icon.star class="size-6 text-irdi-gold" />
                        </div>
                    </div>

                    <p class="mt-4 text-sm leading-6 text-zinc-600">
                        {{ __('statistics.cards.charter_members_description') }}
                    </p>
                </flux:card>

                <flux:card class="group p-7 transition hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-zinc-500">
                                {{ __('statistics.cards.public_directory_members') }}
                            </p>

                            <p class="mt-2 text-4xl font-bold tracking-tight text-irdi-green">
                                {{ number_format($this->publicDirectoryMemberCount) }}
                            </p>
                        </div>

                        <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-irdi-green/10">
                            <flux:icon.identification class="size-6 text-irdi-gold" />
                        </div>
                    </div>

                    <p class="mt-4 text-sm leading-6 text-zinc-600">
                        {{ __('statistics.cards.public_directory_members_description') }}
                    </p>
                </flux:card>
            </div>
        </div>
    </section>

    {{-- Members around the world --}}
    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    {{ __('statistics.countries.heading') }}
                </h2>

                <p class="mt-4 text-lg leading-8 text-zinc-600">
                    {{ __('statistics.countries.description') }}
                </p>
            </div>

            @if ($this->membersWithCountryCount > 0)
                <div class="mt-12 grid gap-8 lg:grid-cols-2 lg:items-start">
                    <flux:card class="p-6 sm:p-8">
                        <flux:chart wire:model="chartCountryData">
                            <flux:chart.viewport class="relative mx-auto aspect-square w-full max-w-md">
                                <flux:chart.svg>
                                    <flux:chart.pie
                                        field="value"
                                        label-field="label"
                                        inner-radius="62%"
                                        radius="4"
                                        class="transition-opacity data-inactive:opacity-40"
                                    />
                                </flux:chart.svg>

                                <div class="pointer-events-none absolute inset-0 grid place-items-center">
                                    <div class="text-center">
                                        <p class="text-4xl font-bold tracking-tight text-irdi-green">
                                            {{ number_format($this->countriesRepresentedCount) }}
                                        </p>
                                        <p class="mt-1 max-w-28 text-sm font-medium leading-5 text-zinc-500 sm:max-w-none">
                                            {{ trans_choice('statistics.countries.center_label', $this->countriesRepresentedCount) }}
                                        </p>
                                    </div>
                                </div>
                            </flux:chart.viewport>

                            <flux:chart.tooltip>
                                <flux:chart.tooltip.value
                                    label-field="label"
                                    field="value"
                                >
                                    <flux:chart.tooltip.indicator />
                                </flux:chart.tooltip.value>
                            </flux:chart.tooltip>
                        </flux:chart>
                    </flux:card>

                    <flux:card class="overflow-hidden p-0">
                        <div class="border-b border-zinc-200 px-6 py-5 dark:border-zinc-700">
                            <h3 class="text-lg font-semibold text-irdi-green">
                                {{ __('statistics.countries.ranking_heading') }}
                            </h3>
                            <p class="mt-1 text-sm text-zinc-500">
                                {{ __('statistics.countries.ranking_description') }}
                            </p>
                        </div>

                        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($countryData as $country)
                                <div class="grid grid-cols-[1fr_auto_auto] items-center gap-4 px-6 py-4">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <flux:flag country="{{ $country['country_code'] }}" size="sm" class="shrink-0" />
                                        <span class="truncate font-medium text-zinc-900 dark:text-white">
                                            {{ $country['label'] }}
                                        </span>
                                    </div>

                                    <div class="text-right">
                                        <span class="font-semibold tabular-nums text-zinc-900 dark:text-white">
                                            {{ number_format($country['value']) }}
                                        </span>
                                        <span class="ml-1 hidden text-sm text-zinc-500 sm:inline">
                                            {{ trans_choice('statistics.countries.member_label', $country['value']) }}
                                        </span>
                                    </div>

                                    <div class="w-16 text-right text-sm font-medium tabular-nums text-zinc-500">
                                        {{ number_format($country['percentage'], 1) }}%
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </flux:card>
                </div>

                <p class="mx-auto mt-8 max-w-3xl text-center text-sm leading-6 text-zinc-500">
                    {{ __('statistics.countries.privacy_note') }}
                </p>
            @else
                <flux:card class="mx-auto mt-12 max-w-2xl p-8 text-center">
                    <flux:icon.globe-alt class="mx-auto size-10 text-irdi-gold" />
                    <h3 class="mt-4 text-lg font-semibold text-irdi-green">
                        {{ __('statistics.countries.empty_heading') }}
                    </h3>
                    <p class="mt-2 text-sm leading-6 text-zinc-600">
                        {{ __('statistics.countries.empty_description') }}
                    </p>
                </flux:card>
            @endif
        </div>
    </section>

    {{-- Membership growth --}}
    <section class="bg-zinc-50">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    {{ __('statistics.growth.heading') }}
                </h2>

                <p class="mt-4 text-lg leading-8 text-zinc-600">
                    {{ __('statistics.growth.description') }}
                </p>
            </div>

            @if ($membershipGrowthData !== [])
                <flux:card class="mt-12 p-6 sm:p-8">
                    <flux:chart wire:model="membershipGrowthData">
                        <flux:chart.viewport class="h-72 sm:h-80">
                            <flux:chart.svg>
                                <flux:chart.area
                                    field="members"
                                    class="text-irdi-gold/20"
                                />

                                <flux:chart.line
                                    field="members"
                                    class="text-irdi-green"
                                />

                                <flux:chart.axis axis="x" field="month" tick-count="8">
                                    <flux:chart.axis.tick />
                                    <flux:chart.axis.line />
                                </flux:chart.axis>

                                <flux:chart.axis axis="y" tick-start="0" :format="['useGrouping' => true, 'maximumFractionDigits' => 0]">
                                    <flux:chart.axis.grid />
                                    <flux:chart.axis.tick />
                                </flux:chart.axis>

                                <flux:chart.cursor />
                            </flux:chart.svg>
                        </flux:chart.viewport>

                        <flux:chart.tooltip>
                            <flux:chart.tooltip.heading field="month" />
                            <flux:chart.tooltip.value
                                field="members"
                                label="{{ __('statistics.growth.members_label') }}"
                                :format="['useGrouping' => true, 'maximumFractionDigits' => 0]"
                            />
                        </flux:chart.tooltip>
                    </flux:chart>
                </flux:card>

                <p class="mx-auto mt-6 max-w-3xl text-center text-sm leading-6 text-zinc-500">
                    {{ __('statistics.growth.note') }}
                </p>
            @else
                <flux:card class="mx-auto mt-12 max-w-2xl p-8 text-center">
                    <flux:icon.chart-bar class="mx-auto size-10 text-irdi-gold" />
                    <h3 class="mt-4 text-lg font-semibold text-irdi-green">
                        {{ __('statistics.growth.empty_heading') }}
                    </h3>
                    <p class="mt-2 text-sm leading-6 text-zinc-600">
                        {{ __('statistics.growth.empty_description') }}
                    </p>
                </flux:card>
            @endif
        </div>
    </section>

</div>
