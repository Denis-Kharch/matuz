@extends('layouts.site')

@section('content')
    @foreach (['hero', 'marquee', 'about', 'directions', 'cases', 'events', 'partners', 'faq', 'gallery', 'certificates', 'contacts'] as $section)
        @includeIf('sections.'.$section)
    @endforeach
@endsection
