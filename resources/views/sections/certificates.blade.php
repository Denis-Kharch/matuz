{{-- Компактный список: маленькое превью, год, название и описание в две строки. --}}
<x-section id="certificates" title="Регалии и сертификаты" kicker="Регалии" :compact="true" class="border-t border-line">
    <ul class="grid gap-2 md:grid-cols-2">
        @foreach ($c->certificates() as $cert)
            <li class="flex items-center gap-3 rounded-xl border border-line bg-surface-2/40 p-2 pr-3 sm:gap-4 sm:p-2.5 sm:pr-4" title="{{ $cert['description'] }}">
                <div class="size-12 shrink-0 overflow-hidden rounded-lg sm:size-[4.5rem]">
                    <x-picture :src="$cert['image']" :alt="$cert['title']" sizes="4.5rem" img-class="h-full w-full object-cover" />
                </div>
                <div class="min-w-0">
                    <p class="font-display text-[1.02rem] leading-snug text-text"><span class="mr-2 text-sm text-sand">{{ $cert['year'] }}</span>{{ $cert['title'] }}</p>
                    <p class="mt-0.5 hidden text-sm text-muted sm:line-clamp-2">{{ $cert['description'] }}</p>
                </div>
            </li>
        @endforeach
    </ul>
    <div class="mt-6">
        <x-kp-button source="certificates" variant="ghost" class="link-draw" />
    </div>
</x-section>
