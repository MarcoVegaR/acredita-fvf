@component('mail::message')
# {{ $greeting ?? 'Hola' }}

{!! $introLines[0] ?? '' !!}

@if (isset($introLines[1]))
{!! $introLines[1] !!}

{!! $introLines[2] ?? '' !!}
@endif

@if (isset($introLines[3]))
{!! $introLines[3] !!}

{!! $introLines[4] ?? '' !!}
@endif

@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
@component('mail::button', ['url' => $actionUrl, 'color' => $color])
{{ $actionText }}
@endcomponent
@endisset

@if (isset($outroLines[0]))
{!! $outroLines[0] !!}
@endif

@if (isset($salutation))
{{ $salutation }}
@else
Saludos,<br>
{{ config('app.name') }}
@endif
@endcomponent
