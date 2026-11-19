@props(['name','size'=>20])
@php($icons=json_decode(file_get_contents(resource_path('icons.json')),true))
<svg {{ $attributes->class(['lucide','icon']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$name] ?? $icons['file-text'] !!}</svg>
