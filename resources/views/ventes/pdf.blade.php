<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $vente->numero_facture }}</title>
    <style>
        @page { margin: 18mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #191919; }
        .entete { width: 100%; margin-bottom: 24px; }
        .entete td { vertical-align: top; }
        .societe { font-size: 15px; font-weight: bold; }
        .meta { text-align: right; }
        .titre { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
        .bloc { background: #f4f6f8; padding: 10px; margin-bottom: 18px; }
        .bloc .etiquette { color: #666; font-size: 9px; text-transform: uppercase; }
        table.lignes { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.lignes th { background: #1f3a5f; color: #fff; padding: 6px; text-align: left; font-size: 9px; text-transform: uppercase; }
        table.lignes td { padding: 6px; border-bottom: 1px solid #e5e5e5; }
        .num { text-align: right; white-space: nowrap; }
        table.totaux { width: 45%; margin-left: 55%; border-collapse: collapse; }
        table.totaux td { padding: 5px 6px; }
        table.totaux .final td { border-top: 2px solid #1f3a5f; font-size: 13px; font-weight: bold; }
        .statut { display: inline-block; padding: 3px 8px; font-size: 9px; text-transform: uppercase; }
        .annulee { background: #f8d7da; color: #842029; }
        .brouillon { background: #e2e3e5; color: #41464b; }
        .pied { margin-top: 28px; color: #666; font-size: 9px; border-top: 1px solid #e5e5e5; padding-top: 8px; }
    </style>
</head>
<body>
@php
    $dt = fn ($v) => number_format((float) $v, 3, ',', ' ').' DT';
    $reste = (float) $vente->total - $encaisse;
@endphp

<table class="entete">
    <tr>
        <td>
            <p class="societe">{{ $parametres['societe_nom'] ?? 'Atelier cache-valises' }}</p>
            @if (! empty($parametres['societe_adresse']))<p>{{ $parametres['societe_adresse'] }}</p>@endif
            @if (! empty($parametres['societe_telephone']))<p>Tel : {{ $parametres['societe_telephone'] }}</p>@endif
        </td>
        <td class="meta">
            <p class="titre">FACTURE</p>
            <p><strong>{{ $vente->numero_facture }}</strong></p>
            <p>{{ $vente->date_vente->format('d/m/Y') }}</p>
            @if ($vente->statut !== 'validee')
                <p><span class="statut {{ $vente->statut }}">{{ $vente->statut }}</span></p>
            @endif
        </td>
    </tr>
</table>

<div class="bloc">
    <span class="etiquette">Client</span><br>
    @if ($vente->client)
        <strong>{{ $vente->client->nom }}</strong><br>
        {{ $vente->client->adresse }}
        @if ($vente->client->telephone)<br>Tel : {{ $vente->client->telephone }}@endif
    @else
        <strong>Vente comptoir{{ $vente->nom_comptoir ? ' — '.$vente->nom_comptoir : '' }}</strong>
    @endif
</div>

<table class="lignes">
    <thead>
        <tr>
            <th>Designation</th><th>Lot</th>
            <th class="num">Qte</th><th class="num">Prix unitaire</th>
            <th class="num">Remise</th><th class="num">Montant</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($vente->lignes as $ligne)
            <tr>
                <td>{{ $ligne->designation }}</td>
                <td>{{ $ligne->lot->numero }}</td>
                <td class="num">{{ $ligne->quantite }}</td>
                <td class="num">{{ $dt($ligne->prix_unitaire) }}</td>
                <td class="num">{{ rtrim(rtrim(number_format((float) $ligne->remise_pourcent, 2, ',', ''), '0'), ',') }}%</td>
                <td class="num">{{ $dt($ligne->montant_ligne) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="totaux">
    <tr><td>Sous-total</td><td class="num">{{ $dt($vente->sous_total) }}</td></tr>
    <tr><td>Remises</td><td class="num">− {{ $dt($vente->total_remise) }}</td></tr>
    <tr class="final"><td>Total a payer</td><td class="num">{{ $dt($vente->total) }}</td></tr>
    @if ($encaisse > 0)
        <tr><td>Deja regle</td><td class="num">{{ $dt($encaisse) }}</td></tr>
        <tr><td><strong>Reste du</strong></td><td class="num"><strong>{{ $dt($reste) }}</strong></td></tr>
    @endif
</table>

@if ($vente->reglements->isNotEmpty())
    <p style="margin-top:22px;"><strong>Reglements</strong></p>
    <table class="lignes">
        <thead><tr><th>Date</th><th>Mode</th><th>Reference</th><th class="num">Montant</th></tr></thead>
        <tbody>
            @foreach ($vente->reglements as $reglement)
                <tr>
                    <td>{{ $reglement->date_reglement->format('d/m/Y') }}</td>
                    <td>{{ ucfirst($reglement->mode) }}</td>
                    <td>{{ $reglement->reference ?: '—' }}</td>
                    <td class="num">{{ $dt($reglement->montant) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<p class="pied">
    Montants en dinars tunisiens (DT), trois decimales.
    @if ($vente->notes){{ $vente->notes }}@endif
</p>
</body>
</html>
