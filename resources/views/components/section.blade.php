@props(['id', 'title', 'kicker' => null, 'lead' => null])
<section id="{{ $id }}" aria-labelledby="{{ $id }}-title" {{ $attributes->class('py-20 md:py-28') }}>
    <div class="wrap">
        <header class="mb-10 max-w-3xl md:mb-14">
            @if ($kicker)
                <p class="kicker mb-4">{{ $kicker }}</p>
            @endif
            <h2 id="{{ $id }}-title" class="text-[clamp(2rem,4.2vw,3.25rem)] text-text">{{ $title }}</h2>
            @if ($lead)
                <p class="mt-5 text-lg text-muted">{{ $lead }}</p>
            @endif
        </header>
        {{ $slot }}
    </div>
</section>
