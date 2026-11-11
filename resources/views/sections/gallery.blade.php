@php
    $gallery = $c->gallery();
    $items = collect($gallery)->map(fn ($g) => [
        'src' => \App\Support\ImageManifest::url($g['image']) ?? '',
        'caption' => $g['caption'],
    ])->all();
@endphp
<section id="gallery" aria-labelledby="gallery-title" class="border-t border-line py-14 md:py-20"
         x-data="lightbox(@js($items))" x-on:keydown.window="onKey($event)">
    <div class="wrap">
        {{-- Одна лента фото: свайп, мышь (потянуть), стрелки; полоса прогресса. --}}
        <div x-data="strip">
        <header class="mb-6 flex items-end justify-between gap-4 md:mb-8">
            <div class="max-w-3xl">
                <p class="kicker mb-4">Галерея</p>
                <h2 id="gallery-title" class="text-[clamp(1.75rem,3.4vw,2.5rem)] text-text">Галерея</h2>
            </div>
            <x-strip-arrows label="Листать галерею" class="hidden sm:flex" />
        </header>
        <ul x-ref="track" data-gallery-strip
            class="-mx-4 flex gap-3 overflow-x-auto scroll-px-4 px-4 pb-3 [scrollbar-width:none] sm:mx-0 sm:scroll-px-0 sm:px-0 [&::-webkit-scrollbar]:hidden"
            :class="dragging ? 'cursor-grabbing select-none' : 'snap-x snap-mandatory sm:cursor-grab'"
            x-on:pointerdown="down($event)" x-on:pointermove="move($event)" x-on:pointerup="up()" x-on:pointercancel="up()"
            x-on:click.capture="guardClick($event)" x-on:dragstart.prevent>
            @foreach ($gallery as $i => $g)
                <li class="shrink-0 snap-start">
                    <button type="button" data-gallery-item x-on:click="open({{ $i }})" aria-label="Открыть фото: {{ $g['caption'] }}"
                            class="group block h-56 overflow-hidden rounded-[var(--radius-card)] ring-1 ring-line sm:h-64 lg:h-72 {{ $i % 3 === 1 ? 'aspect-[3/4]' : 'aspect-[4/3]' }}">
                        <x-picture :src="$g['image']" :alt="$g['caption']" sizes="(min-width: 1024px) 24rem, 18rem"
                                   img-class="pointer-events-none h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" />
                    </button>
                </li>
            @endforeach
        </ul>
        <x-strip-progress class="mt-2" />
        </div>

    </div>

    <x-modal name="gallery" label="Галерея" :fullscreen="true" x-bind:data-index="index" panel-class="!bg-[#050909]">
        <div class="flex h-full flex-col">
            <div data-lightbox-stage class="relative flex flex-1 touch-pan-y items-center justify-center px-4 pt-20 pb-4 select-none sm:px-20"
                 x-on:pointerdown="down($event)" x-on:pointerup="up($event)">
                <img :src="current.src" :alt="current.caption" class="max-h-full max-w-full rounded-xl object-contain" draggable="false">
                <button type="button" x-on:click="prev()" aria-label="Предыдущее фото"
                        class="absolute left-3 top-1/2 hidden size-12 -translate-y-1/2 place-items-center rounded-full border border-line bg-bg/70 text-text transition hover:border-accent hover:text-accent-2 sm:grid">←</button>
                <button type="button" x-on:click="next()" aria-label="Следующее фото"
                        class="absolute right-3 top-1/2 hidden size-12 -translate-y-1/2 place-items-center rounded-full border border-line bg-bg/70 text-text transition hover:border-accent hover:text-accent-2 sm:grid">→</button>
            </div>
            <div class="wrap flex items-center justify-between gap-4 pb-6 text-sm">
                <p class="text-text/85" x-text="current.caption"></p>
                <p class="shrink-0 font-display text-muted" x-text="(index + 1) + ' / ' + items.length"></p>
            </div>
        </div>
    </x-modal>
</section>
