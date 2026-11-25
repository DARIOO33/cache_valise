@php
    $libelles = ['espece' => 'Especes', 'virement' => 'Virement', 'cheque' => 'Cheque'];
    $couleurs = ['espece' => 'success', 'virement' => 'info', 'cheque' => 'secondary'];
@endphp
<span class="badge text-bg-{{ $couleurs[$mode] ?? 'secondary' }}">{{ $libelles[$mode] ?? $mode }}</span>
