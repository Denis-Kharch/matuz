@php($contacts = $c->contacts())
<section id="contacts" aria-labelledby="contacts-title" class="relative overflow-hidden border-t border-line py-24 md:py-32">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 bg-[radial-gradient(50rem_26rem_at_50%_110%,rgb(237_153_84/0.16),transparent_70%),radial-gradient(40rem_24rem_at_10%_0%,rgb(7_100_101/0.22),transparent_70%)]"></div>
    <div class="wrap relative">
        <header class="mx-auto max-w-3xl text-center">
            <p class="kicker mb-5 justify-center">Контакты</p>
            <h2 id="contacts-title" class="text-[clamp(2.1rem,5vw,4rem)] text-text">Всё уже в ваших руках, остался последний шаг</h2>
            <p class="mx-auto mt-5 max-w-xl text-lg text-muted">Напишите удобным способом или получите КП прямо сейчас — нужно только название компании.</p>
            <div class="mt-9">
                <x-kp-button source="contacts" variant="primary" class="px-8 text-base" />
            </div>
        </header>
        <ul class="mx-auto mt-14 grid max-w-4xl gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['href' => $contacts['phone_href'], 'label' => 'Телефон', 'value' => $contacts['phone'], 'icon' => 'phone'],
                ['href' => $contacts['telegram'], 'label' => 'Telegram', 'value' => '@'.\Illuminate\Support\Str::afterLast($contacts['telegram'], '/'), 'icon' => 'telegram'],
                ['href' => $contacts['vk'], 'label' => 'ВКонтакте', 'value' => \Illuminate\Support\Str::after($contacts['vk'], 'https://'), 'icon' => 'vk'],
                ['href' => $contacts['max'], 'label' => 'MAX', 'value' => 'Написать в MAX', 'icon' => 'max'],
            ] as $link)
                <li>
                    <a href="{{ $link['href'] }}" @unless (str_starts_with($link['href'], 'tel:')) target="_blank" rel="noopener" @endunless
                       class="card group flex h-full items-center gap-4 p-5 transition duration-300 hover:-translate-y-0.5 hover:border-sand/50">
                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-surface-2 text-sand transition group-hover:text-accent-2">
                            @include('partials.icon', ['name' => $link['icon'], 'class' => 'size-5'])
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm text-muted">{{ $link['label'] }}</span>
                            <span class="block truncate font-display text-text">{{ $link['value'] }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
