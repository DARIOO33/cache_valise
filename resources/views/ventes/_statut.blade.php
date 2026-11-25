@php
    $libelles = ['brouillon' => 'Brouillon', 'validee' => 'Validee', 'annulee' => 'Annulee'];
    $couleurs = ['brouillon' => 'secondary', 'validee' => 'success', 'annulee' => 'danger'];
@endphp
<span class="badge text-bg-{{ $couleurs[$statut] ?? 'secondary' }}">{{ $libelles[$statut] ?? $statut }}</span>
