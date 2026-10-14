@props([
    'color' => 'primary',
    'light' => true,
    'dot' => false,
    'animated' => false,
])

@php
    $badgeClass = 'badge ' . ($light ? "bg-{$color}-lt" : "bg-{$color}");
@endphp

<span {{ $attributes->merge(['class' => $badgeClass]) }}>
    @if ($dot)
        <span class="status-dot {{ $animated ? 'status-dot-animated' : '' }} bg-{{ $color }} me-1"></span>
    @endif
    {{ $slot }}
</span>
