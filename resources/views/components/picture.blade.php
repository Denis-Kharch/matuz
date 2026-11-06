@props([
    'src',
    'alt' => '',
    'sizes' => '100vw',
    'eager' => false,
    'focal' => null,
    'mobile' => null,
    'imgClass' => 'h-full w-full object-cover',
])
@php
    $meta = \App\Support\ImageManifest::get($src);
    $set = fn (string $key, string $ext) => collect(\App\Support\ImageManifest::get($key)['widths'])
        ->map(fn ($w) => asset("img/{$key}-{$w}.{$ext}")." {$w}w")->implode(', ');
    $fallbackWidth = collect($meta['widths'])->first(fn ($w) => $w >= 1280) ?? last($meta['widths']);
    $style = $focal ? "object-position: {$focal['x']}% {$focal['y']}%" : null;
@endphp
<picture {{ $attributes }}>
    @if ($mobile)
        <source media="(max-width: 639px)" type="image/avif" srcset="{{ $set($mobile, 'avif') }}" sizes="100vw">
        <source media="(max-width: 639px)" type="image/webp" srcset="{{ $set($mobile, 'webp') }}" sizes="100vw">
    @endif
    <source type="image/avif" srcset="{{ $set($src, 'avif') }}" sizes="{{ $sizes }}">
    <source type="image/webp" srcset="{{ $set($src, 'webp') }}" sizes="{{ $sizes }}">
    <img
        src="{{ asset("img/{$src}-{$fallbackWidth}.webp") }}"
        alt="{{ $alt }}"
        width="{{ $meta['width'] }}"
        height="{{ $meta['height'] }}"
        decoding="async"
        @if ($eager) fetchpriority="high" @else loading="lazy" @endif
        @if ($style) style="{{ $style }}" @endif
        class="{{ $imgClass }}"
    >
</picture>
