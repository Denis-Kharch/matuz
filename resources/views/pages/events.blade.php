@extends('layouts.site')

@if ($openEvent)
    @section('title', $openEvent['title'].' · Андрей Матузов')
    @section('description', \Illuminate\Support\Str::limit(\App\Support\RichText::plain($openEvent['description']), 155))
@else
    @section('title', 'Лента событий · Андрей Матузов')
    @section('description', 'Тренинги, стратегические сессии, форумы и выступления Андрея Матузова — свежие события с фотографиями.')
@endif

@section('content')
    @php($events = $c->events())
    <section aria-labelledby="feed-title" class="pt-32 pb-20 md:pt-40 md:pb-28">
        <div class="wrap">
            <a href="{{ route('home') }}" class="link-draw text-sm text-muted hover:text-text">← На главную</a>
            <header class="mt-8 max-w-3xl">
                <p class="kicker mb-4">События</p>
                <h1 id="feed-title" class="text-[clamp(2.25rem,5vw,3.75rem)] text-text">Лента событий</h1>
                <p class="mt-5 text-lg text-muted">Тренинги, стратегические сессии, форумы и выступления — от свежих к ранним.</p>
            </header>
            <ul class="mt-12 divide-y divide-line border-y border-line">
                @foreach ($events as $event)
                    @include('events.item', ['event' => $event, 'base' => route('events.index')])
                @endforeach
            </ul>
            <div class="mt-10">
                <x-kp-button source="events-page" variant="primary" />
            </div>
        </div>
    </section>

    @if ($openEvent)
        <div hidden data-open-overlay="event-{{ $openEvent['slug'] }}" data-overlay-base="{{ route('events.index') }}"></div>
    @endif

    @push('modals')
        @foreach ($events as $event)
            @include('events.overlay', ['event' => $event])
        @endforeach
    @endpush
@endsection
