@php
    $libelles = ['tissu' => 'Tissu', 'couture' => 'Couture', 'impression' => 'Impression', 'autre' => 'Autre'];
    $couleurs = ['tissu' => 'primary', 'couture' => 'info', 'impression' => 'warning', 'autre' => 'secondary'];
@endphp
<span class="badge text-bg-{{ $couleurs[$type] ?? 'secondary' }}">{{ $libelles[$type] ?? $type }}</span>
