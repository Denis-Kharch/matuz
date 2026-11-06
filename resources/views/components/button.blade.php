@props(['variant' => 'primary', 'href' => null, 'type' => 'button'])
@php($classes = 'btn btn-'.$variant)
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
