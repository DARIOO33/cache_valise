@php
    $libelles = ['en_preparation' => 'En preparation', 'termine' => 'Termine', 'annule' => 'Annule'];
    $couleurs = ['en_preparation' => 'warning', 'termine' => 'success', 'annule' => 'danger'];
@endphp
<span class="badge text-bg-{{ $couleurs[$etat] ?? 'secondary' }}">{{ $libelles[$etat] ?? $etat }}</span>
