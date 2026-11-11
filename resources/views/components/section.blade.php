@props(['id', 'title', 'kicker' => null, 'lead' => null, 'compact' => false])
{{-- compact — блоки-«справки» (партнёры, регалии, галерея): меньше воздуха сверху/снизу и под заголовком. --}}
<section id="{{ $id }}" aria-labelledby="{{ $id }}-title" {{ $attributes->class($compact ? 'py-14 md:py-20' : 'py-20 md:py-28') }}>
    <div class="wrap">
        <header @class(['max-w-3xl', 'mb-6 md:mb-8' => $compact, 'mb-10 md:mb-14' => ! $compact])>
            @if ($kicker)
                <p class="kicker mb-4">{{ $kicker }}</p>
            @endif
            <h2 id="{{ $id }}-title" @class(['text-text', 'text-[clamp(1.75rem,3.4vw,2.5rem)]' => $compact, 'text-[clamp(2rem,4.2vw,3.25rem)]' => ! $compact])>{{ $title }}</h2>
            @if ($lead)
                <p class="mt-5 text-lg text-muted">{{ $lead }}</p>
            @endif
        </header>
        {{ $slot }}
    </div>
</section>
