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
        <ul data-partners-grid class="grid-cols-4 gap-2 sm:grid-cols-6 lg:grid-cols-8 {{ $gridClass }}">
            @foreach ($partners as $p)
                <li class="group relative" title="{{ $p['name'] }} — {{ $p['description'] }}">
                    <span class="sr-only">{{ $p['name'] }} — {{ $p['description'] }}</span>
                    @if ($p['url'])<a href="{{ $p['url'] }}" target="_blank" rel="noopener" class="block">@endif
                    {{-- Все плитки — одинаковые квадраты. Логотип вписан целиком на белой подложке: если пропорции
                         другие — по краям белые поля. Без логотипа — короткое имя на тёмной плитке. --}}
                    <div @class([
                        'relative grid aspect-square place-items-center overflow-hidden rounded-xl border p-2 transition sm:p-3',
                        'border-white bg-white group-hover:border-sand' => $p['logo'],
                        'border-line bg-surface-2/60 group-hover:border-sand/50' => ! $p['logo'],
                    ])>
                        @if ($p['logo'])
                            <x-picture :src="$p['logo']" :alt="$p['name']" sizes="10rem" class="absolute inset-2 sm:inset-3" img-class="h-full w-full object-contain" />
                        @else
                            <span class="text-center font-display text-xs leading-tight font-semibold tracking-wide text-sand/90 sm:text-sm">{{ $p['short'] }}</span>
                        @endif
                    </div>
                    <p class="mt-1.5 truncate text-center text-xs text-muted"><span aria-hidden="true">{{ $p['name'] }}</span></p>
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
                            <div @class([
                                'relative mr-3 grid size-20 shrink-0 place-items-center overflow-hidden rounded-xl border p-2 sm:size-24',
                                'border-white bg-white' => $p['logo'],
                                'border-line bg-surface-2/60' => ! $p['logo'],
                            ])>
                                @if ($p['logo'])
                                    <x-picture :src="$p['logo']" alt="" sizes="8rem" class="absolute inset-2" img-class="h-full w-full object-contain" />
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
