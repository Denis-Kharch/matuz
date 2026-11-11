@php
    $partners = $c->partners();
    $display = $c->partnersDisplay();
    // Бегущая строка: везде или только на телефоне; иначе — компактная сетка логотипов.
    $marquee = $display['marquee'];
    $gridClass = $display['mobile_only'] ? 'hidden md:grid' : 'grid';
    $marqueeClass = $display['mobile_only'] ? 'md:hidden' : '';
@endphp
<x-section id="partners" title="Друзья и партнёры" kicker="Партнёры" :compact="true" class="border-t border-line" data-partners-mode="{{ $marquee ? ($display['mobile_only'] ? 'marquee-mobile' : 'marquee') : 'grid' }}">
    @if (! $marquee || $display['mobile_only'])
        <ul data-partners-grid class="grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-8 {{ $gridClass }}">
            @foreach ($partners as $p)
                <li class="group" title="{{ $p['name'] }} — {{ $p['description'] }}">
                    @if ($p['url'])<a href="{{ $p['url'] }}" target="_blank" rel="noopener" class="block">@endif
                    <div class="grid aspect-[3/2] place-items-center rounded-xl lg:aspect-[2/1] border border-line bg-surface-2/60 p-3 transition group-hover:border-sand/50">
                        @if ($p['logo'])
                            <x-picture :src="$p['logo']" :alt="$p['name']" sizes="10rem" img-class="max-h-full max-w-full object-contain opacity-85 transition group-hover:opacity-100" />
                        @else
                            <span class="font-display text-sm font-semibold tracking-wide whitespace-nowrap text-sand/90 sm:text-base">{{ $p['short'] }}</span>
                        @endif
                    </div>
                    <p class="mt-1.5 truncate text-center text-xs text-muted">{{ $p['name'] }}<span class="sr-only"> — {{ $p['description'] }}</span></p>
                    @if ($p['url'])</a>@endif
                </li>
            @endforeach
        </ul>
    @endif

    @if ($marquee)
        <div data-partners-marquee class="{{ $marqueeClass }}">
            <ul class="sr-only">
                @foreach ($partners as $p)
                    <li>{{ $p['name'] }} — {{ $p['description'] }}</li>
                @endforeach
            </ul>
            <div class="marquee group -mx-4 overflow-hidden [mask-image:linear-gradient(to_right,transparent,#000_8%,#000_92%,transparent)] sm:mx-0" aria-hidden="true">
                <div data-marquee-track class="marquee-track marquee-track--fast flex w-max items-center group-hover:[animation-play-state:paused]">
                    @foreach ([0, 1] as $copy)
                        @foreach ($partners as $p)
                            <div class="mr-3 grid h-14 w-28 shrink-0 place-items-center rounded-xl border border-line bg-surface-2/60 px-3 sm:h-16 sm:w-32">
                                @if ($p['logo'])
                                    <x-picture :src="$p['logo']" alt="" sizes="8rem" img-class="max-h-10 max-w-full object-contain opacity-85" />
                                @else
                                    <span class="font-display text-sm font-semibold tracking-wide whitespace-nowrap text-sand/90">{{ $p['short'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="mt-6">
        <x-kp-button source="partners" variant="ghost" class="link-draw" />
    </div>
</x-section>
