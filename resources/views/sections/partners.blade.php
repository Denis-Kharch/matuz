<x-section id="partners" title="Друзья и партнёры" kicker="Партнёры" class="border-t border-line">
    <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($c->partners() as $p)
            <li class="card flex flex-col overflow-hidden">
                <div class="grid aspect-[16/9] place-items-center bg-surface-2 p-6">
                    @if ($p['logo'])
                        <x-picture :src="$p['logo']" :alt="$p['name']" sizes="20rem" img-class="max-h-full max-w-full object-contain" />
                    @else
                        <span class="font-display text-2xl font-semibold tracking-wide text-sand/90">{{ $p['short'] }}</span>
                    @endif
                </div>
                <div class="p-5">
                    <p class="font-display text-lg leading-snug text-text">{{ $p['name'] }}</p>
                    <p class="mt-1.5 text-[0.95rem] text-muted">{{ $p['description'] }}</p>
                </div>
            </li>
        @endforeach
    </ul>
    <div class="mt-8">
        <x-kp-button source="partners" variant="ghost" class="link-draw" />
    </div>
</x-section>
