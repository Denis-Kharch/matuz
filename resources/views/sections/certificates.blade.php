<x-section id="certificates" title="Регалии и сертификаты" kicker="Регалии" class="border-t border-line">
    <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($c->certificates() as $cert)
            <li class="card group flex flex-col overflow-hidden">
                <div class="relative aspect-[16/10] overflow-hidden">
                    <x-picture :src="$cert['image']" alt="" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" img-class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" />
                    <span class="absolute top-3 left-3 rounded-full border border-sand/50 bg-bg/75 px-3 py-1 font-display text-sm text-sand backdrop-blur">{{ $cert['year'] }}</span>
                </div>
                <div class="p-5">
                    <p class="font-display text-lg leading-snug text-text">{{ $cert['title'] }}</p>
                    <p class="mt-1.5 text-[0.95rem] text-muted">{{ $cert['description'] }}</p>
                </div>
            </li>
        @endforeach
    </ul>
    <div class="mt-8">
        <x-kp-button source="certificates" variant="ghost" class="link-draw" />
    </div>
</x-section>
