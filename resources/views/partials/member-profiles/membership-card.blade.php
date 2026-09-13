<div class="mx-auto max-w-xl overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
    <div class="border-t-4 border-irdi-gold">

        <div class="grid grid-cols-[76px_minmax(0,1fr)_124px] items-center gap-4 p-5">

            <div>
                <img
                    src="{{ asset('irdi-logo.png') }}"
                    alt=""
                    aria-hidden="true"
                    class="h-[76px] w-[76px] object-contain"
                >
            </div>

            <div class="min-w-0">

                <p class="text-[0.6875rem] font-bold uppercase tracking-[0.18em] text-irdi-gold-text">
                    {{ __('Official IRDI Member') }}
                </p>

                <h2 class="mt-1 truncate text-xl font-bold leading-tight text-irdi-green">
                    {{ $profile->profile_name }}
                </h2>

                <p class="mt-0.5 text-sm text-zinc-500">
                    {{ '@' . $profile->username }}
                </p>

                <div class="mt-2 flex flex-wrap items-center gap-2">

                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-green-700">
                        <span class="h-2 w-2 rounded-full bg-green-500"></span>
                        {{ __('Active Member') }}
                    </span>

                    @if ($profile->user->is_super_admin)
                        <flux:badge color="red" size="sm">
                            {{ __('Super Administrator') }}
                        </flux:badge>
                    @elseif ($profile->user->is_admin)
                        <flux:badge color="red" size="sm">
                            {{ __('Administrator') }}
                        </flux:badge>
                    @elseif ($profile->user->is_moderator)
                        <flux:badge color="blue" size="sm">
                            {{ __('Moderator') }}
                        </flux:badge>
                    @endif

                    @if ($profile->user->is_charter_member)
                        <flux:badge color="amber" size="sm">
                            {{ __('Charter Member') }}
                        </flux:badge>
                    @endif

                </div>

                @if ($profile->user->member_since)
                    <p class="mt-2 text-xs text-zinc-500">
                        {{ __('Member Since :date', ['date' => $profile->user->member_since->translatedFormat('F Y')]) }}
                    </p>
                @endif

            </div>

            <div class="shrink-0 text-center">

                <a
                    href="{{ route('member-profiles.show', $profile) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="{{ __('Open verified IRDI member profile') }} ({{ __('common.opens_in_new_tab') }})"
                    class="inline-block rounded-xl border border-irdi-gold/60 bg-white p-1.5"
                >
                    {!! $qrCode !!}
                </a>

                <p class="mt-1 text-[0.625rem] font-bold uppercase tracking-[0.14em] text-irdi-green">
                    {{ __('Scan to Verify') }}
                </p>

            </div>

        </div>

        <div class="border-t border-irdi-gold/40 bg-irdi-green px-5 py-2.5">
            <a
                href="{{ route('member-profiles.show', $profile) }}"
                target="_blank"
                rel="noopener noreferrer"
                class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-[0.625rem] font-medium uppercase tracking-wide text-white/90 hover:text-white"
            >
                <span>{{ __('International Responsible Detectorist Institute') }}</span>
                <span class="normal-case tracking-normal text-white/75">
                    {{ __('Verify at irdi.online') }}
                </span>
                <span class="sr-only">({{ __('common.opens_in_new_tab') }})</span>
            </a>
        </div>

    </div>
</div>
