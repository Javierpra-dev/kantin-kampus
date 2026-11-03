@props([
    'title' => 'Kantin Kampus',
    'hideNav' => false,
])

@include('layouts.buyer', [
    'title' => $title,
    'hideNav' => $hideNav,
    'slot' => $slot,
])
