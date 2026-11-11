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
        {{-- Одна лента фото с прокруткой (свайп, колесо, стрелки) вместо сетки на несколько экранов. --}}
        <header class="mb-6 flex items-end justify-between gap-4 md:mb-8">
            <div class="max-w-3xl">
                <p class="kicker mb-4">Галерея</p>
                <h2 id="gallery-title" class="text-[clamp(1.75rem,3.4vw,2.5rem)] text-text">Галерея</h2>
            </div>
            <div class="hidden shrink-0 gap-2 sm:flex">
                <button type="button" x-on:click="$refs.strip.scrollBy({ left: -$refs.strip.clientWidth * 0.8, behavior: 'smooth' })" aria-label="Листать галерею назад"
                        class="grid size-11 place-items-center rounded-full border border-line text-text transition hover:border-accent hover:text-accent-2">←</button>
                <button type="button" x-on:click="$refs.strip.scrollBy({ left: $refs.strip.clientWidth * 0.8, behavior: 'smooth' })" aria-label="Листать галерею вперёд"
                        class="grid size-11 place-items-center rounded-full border border-line text-text transition hover:border-accent hover:text-accent-2">→</button>
            </div>
        </header>
        <ul x-ref="strip" data-gallery-strip
            class="-mx-4 flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-px-4 px-4 pb-3 [scrollbar-width:thin] sm:mx-0 sm:scroll-px-0 sm:px-0">
            @foreach ($gallery as $i => $g)
                <li class="shrink-0 snap-start">
                    <button type="button" data-gallery-item x-on:click="open({{ $i }})" aria-label="Открыть фото: {{ $g['caption'] }}"
                            class="group block h-56 overflow-hidden rounded-[var(--radius-card)] ring-1 ring-line sm:h-64 lg:h-72 {{ $i % 3 === 1 ? 'aspect-[3/4]' : 'aspect-[4/3]' }}">
                        <x-picture :src="$g['image']" :alt="$g['caption']" sizes="(min-width: 1024px) 24rem, 18rem"
                                   img-class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" />
                    </button>
                </li>
            @endforeach
        </ul>

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
