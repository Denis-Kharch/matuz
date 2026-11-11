@extends('layouts.site')

@push('head')
    {{ \App\Site\StructuredData::script(app(\App\Site\StructuredData::class)->home()) }}
@endpush

@section('content')
    @foreach (['hero', 'marquee', 'about', 'directions', 'cases', 'events', 'partners', 'faq', 'gallery', 'certificates', 'contacts'] as $section)
        @includeIf('sections.'.$section)
    @endforeach
@endsection
