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
    $mobileMeta = $mobile ? \App\Support\ImageManifest::get($mobile) : null;
    $set = fn (array $m, string $ext) => collect($m['widths'])
        ->map(fn ($w) => asset("{$m['base']}-{$w}.{$ext}")." {$w}w")->implode(', ');
    $fallbackWidth = collect($meta['widths'])->first(fn ($w) => $w >= 1280) ?? last($meta['widths']);
    $focal ??= $meta['focal'];
    $style = $focal ? "object-position: {$focal['x']}% {$focal['y']}%" : null;
@endphp
@if ($meta['widths'])
<picture {{ $attributes }}>
    @if ($mobileMeta && $mobileMeta['widths'])
        @foreach ($mobileMeta['formats'] as $ext)
            <source media="(max-width: 639px)" type="image/{{ $ext }}" srcset="{{ $set($mobileMeta, $ext) }}" sizes="100vw">
        @endforeach
    @endif
    @foreach ($meta['formats'] as $ext)
        <source type="image/{{ $ext }}" srcset="{{ $set($meta, $ext) }}" sizes="{{ $sizes }}">
    @endforeach
    <img
        src="{{ asset("{$meta['base']}-{$fallbackWidth}.webp") }}"
        alt="{{ $alt }}"
        width="{{ $meta['width'] }}"
        height="{{ $meta['height'] }}"
        decoding="async"
        @if ($eager) fetchpriority="high" @else loading="lazy" @endif
        @if ($style) style="{{ $style }}" @endif
        class="{{ $imgClass }}"
    >
</picture>
@endif
