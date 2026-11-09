@php
    $gallery = $c->gallery();
    $items = collect($gallery)->map(fn ($g) => [
        'src' => \App\Support\ImageManifest::url($g['image']) ?? '',
        'caption' => $g['caption'],
    ])->all();
    $ratios = ['aspect-[4/5]', 'aspect-square', 'aspect-[4/3]', 'aspect-[3/4]'];
@endphp
<section id="gallery" aria-labelledby="gallery-title" class="border-t border-line py-20 md:py-28"
         x-data="lightbox(@js($items))" x-on:keydown.window="onKey($event)">
    <div class="wrap">
        <header class="mb-10 max-w-3xl md:mb-14">
            <p class="kicker mb-4">Галерея</p>
            <h2 id="gallery-title" class="text-[clamp(2rem,4.2vw,3.25rem)] text-text">Галерея</h2>
        </header>
        <ul class="columns-2 gap-3 md:columns-3 lg:columns-4 [&>li]:mb-3">
            @foreach ($gallery as $i => $g)
                <li class="break-inside-avoid">
                    <button type="button" data-gallery-item x-on:click="open({{ $i }})" aria-label="Открыть фото: {{ $g['caption'] }}"
                            class="group block w-full overflow-hidden rounded-[var(--radius-card)] ring-1 ring-line {{ $ratios[$i % 4] }}">
                        <x-picture :src="$g['image']" :alt="$g['caption']" sizes="(min-width: 1024px) 25vw, (min-width: 768px) 33vw, 50vw"
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
