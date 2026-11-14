@php
    $id = (int) ($status ?? 0);
    $tone = match (true) {
        in_array($id, [10, 11], true) => 'good',
        in_array($id, [1, 9], true) => 'bad',
        in_array($id, [6, 7, 8, 12, 19], true) => 'live',
        default => 'wait',
    };
@endphp
<span class="pill pill-{{ $tone }}">{{ $label }}</span>
