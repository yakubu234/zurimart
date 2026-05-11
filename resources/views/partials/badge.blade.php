@php
    $normalized = str_replace(' ', '_', strtolower($value));
    $classes = match ($normalized) {
        'accepted', 'available', 'active', 'retail' => 'badge-success',
        'pending', 'wholesale' => 'badge-warning',
        'rejected', 'overly_booked', 'suspended', 'failed' => 'badge-danger',
        default => 'badge-info',
    };
@endphp
<span class="badge {{ $classes }}">{{ ucwords(str_replace('_', ' ', $normalized)) }}</span>
