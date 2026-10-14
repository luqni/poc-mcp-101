@props([
    'title' => null,
    'subtitle' => null,
    'actions' => null,
    'class' => '',
    'headerClass' => '',
    'bodyClass' => '',
    'footer' => null,
])

<div {{ $attributes->merge(['class' => 'card ' . $class]) }}>
    @if ($title || $actions)
        <div class="card-header {{ $headerClass }}">
            <div>
                @if ($title)
                    <h3 class="card-title">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="card-subtitle text-secondary mb-0">{{ $subtitle }}</p>
                @endif
            </div>
            @if ($actions)
                <div class="card-actions ms-auto">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="card-body {{ $bodyClass }}">
        {{ $slot }}
    </div>

    @if ($footer)
        <div class="card-footer">
            {{ $footer }}
        </div>
    @endif
</div>
