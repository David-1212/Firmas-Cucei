@props(['href' => null, 'color' => 'view', 'icon' => null])

@php
$class = match ($color) {
    'edit' => 'action-edit',
    'sign' => 'action-sign',
    'danger' => 'action-danger',
    'neutral' => 'action-neutral',
    default => 'action-view',
};
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>
        @if($icon)<span class="h-4 w-4">{!! $icon !!}</span>@endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => $class]) }}>
        @if($icon)<span class="h-4 w-4">{!! $icon !!}</span>@endif
        {{ $slot }}
    </button>
@endif
