@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'submit'])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 disabled:shadow-none';
    $sizes = [
        'md' => 'px-4 py-2.5',
        'sm' => 'px-3 py-1.5 text-sm',
    ];
    $styles = [
        'primary' => 'bg-accent text-white shadow-gloed hover:-translate-y-0.5 hover:bg-accent-dark focus-visible:ring-accent',
        'secondary' => 'bg-white text-brand ring-1 ring-brand/30 hover:bg-brand/5 focus-visible:ring-brand',
        'danger' => 'bg-white text-danger ring-1 ring-danger/40 hover:bg-danger/5 focus-visible:ring-danger',
        'ghost' => 'bg-slate-100 text-slate-600 hover:bg-slate-200 focus-visible:ring-slate-400',
    ];
    $classes = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($styles[$variant] ?? $styles['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
