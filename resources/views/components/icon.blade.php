@props(['name', 'class' => 'size-5'])

@php
    $lucideName = match ($name) {
        'chart' => 'bar-chart-3',
        'users' => 'users',
        'calendar' => 'calendar',
        'mail' => 'mail',
        'bed' => 'bed-single',
        'document' => 'file-text',
        'settings' => 'settings',
        'search' => 'search',
        'bell' => 'bell',
        'chat' => 'message-circle',
        'chevron-down' => 'chevron-down',
        'chevron-right' => 'chevron-right',
        'dots' => 'more-horizontal',
        'minus' => 'minus',
        'plus' => 'plus',
        'flame' => 'flame',
        'trend-up' => 'trending-up',
        'logout' => 'log-out',
        'users-cog' => 'users',
        'restaurant' => 'utensils-crossed',
        'bar' => 'martini',
        'lounge' => 'sofa',
        'till' => 'receipt',
        'inventory' => 'boxes',
        'cart' => 'shopping-cart',
        'wallet' => 'wallet',
        'clock' => 'clock',
        'clipboard' => 'clipboard',
        'alert' => 'alert-triangle',
        'eye' => 'eye',
        'pencil' => 'pencil',
        'x-circle' => 'x-circle',
        'trash' => 'trash-2',
        'user' => 'user-round',
        'moon' => 'moon',
        'sun' => 'sun',
        default => 'circle',
    };
@endphp

<i
    {{ $attributes->merge(['class' => $class]) }}
    data-lucide="{{ $lucideName }}"
    aria-hidden="true"
></i>
