@php($principles = $c->principles())
<section aria-label="Принципы работы" class="intro-block relative border-y border-line bg-surface" style="--d:1.2s">
    <ul class="sr-only">
        @foreach ($principles as $p)
            <li>{{ $p }}</li>
        @endforeach
    </ul>
    <div class="marquee group overflow-hidden py-5" aria-hidden="true">
        <div data-marquee-track class="marquee-track flex w-max items-center group-hover:[animation-play-state:paused]">
            @foreach ([0, 1] as $copy)
                @foreach ($principles as $p)
                    @php([$num, $text] = array_pad(explode(' — ', $p, 2), 2, ''))
                    <span class="flex items-center whitespace-nowrap">
                        <span class="font-display text-[0.95rem] font-semibold tracking-wide text-sand">{{ $num }}</span>
                        <span class="ml-3 text-[1.05rem] text-text/90">{{ $text }}</span>
                        <span class="mx-8 size-1.5 rounded-full bg-accent/80"></span>
                    </span>
                @endforeach
            @endforeach
        </div>
    </div>
</section>
