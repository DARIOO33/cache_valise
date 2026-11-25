@php
    $libelles = [
        'solde_initial' => 'Solde initial', 'alimentation' => 'Alimentation',
        'reglement_client' => 'Reglement client', 'paiement_charge' => 'Paiement charge',
        'retrait' => 'Retrait', 'depot_bancaire' => 'Depot bancaire', 'correction' => 'Correction',
    ];
    $couleurs = [
        'solde_initial' => 'dark', 'alimentation' => 'success', 'reglement_client' => 'primary',
        'paiement_charge' => 'warning', 'retrait' => 'danger', 'depot_bancaire' => 'info',
        'correction' => 'secondary',
    ];
@endphp
<span class="badge text-bg-{{ $couleurs[$type] ?? 'secondary' }}">{{ $libelles[$type] ?? $type }}</span>
