<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport</title>
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #191919; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        h2 { font-size: 11px; margin: 18px 0 6px; border-bottom: 1px solid #ccc; padding-bottom: 3px; }
        .sous-titre { color: #666; font-size: 9px; margin: 0 0 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px 6px; border-bottom: 1px solid #e5e5e5; }
        th { background: #f4f6f8; text-align: left; font-size: 9px; text-transform: uppercase; color: #555; }
        .num { text-align: right; white-space: nowrap; }
        .kpi { width: 100%; margin-bottom: 6px; }
        .kpi td { border: 1px solid #e5e5e5; padding: 8px; width: 25%; }
        .kpi .etiquette { color: #666; font-size: 9px; display: block; margin-bottom: 3px; }
        .kpi .valeur { font-size: 13px; font-weight: bold; }
        .vide { color: #888; font-style: italic; }
        .note { color: #666; font-size: 8px; margin-top: 16px; }
    </style>
</head>
<body>
@php $dt = fn ($v) => number_format((float) $v, 3, ',', ' ').' DT'; @endphp

<h1>Rapport d'activite</h1>
<p class="sous-titre">
    Gestion des cache-valises &middot;
    Periode : {{ $du ? \Carbon\Carbon::parse($du)->format('d/m/Y') : 'origine' }}
    au {{ $au ? \Carbon\Carbon::parse($au)->format('d/m/Y') : now()->format('d/m/Y') }}
    &middot; edite le {{ now()->format('d/m/Y H:i') }}
</p>

<table class="kpi">
    <tr>
        <td><span class="etiquette">Chiffre d'affaires</span><span class="valeur">{{ $dt($chiffreAffaires) }}</span></td>
        <td><span class="etiquette">Cout des produits vendus</span><span class="valeur">{{ $dt($cogs) }}</span></td>
        <td><span class="etiquette">Marge brute</span><span class="valeur">{{ $dt($marge) }}</span></td>
        <td><span class="etiquette">Solde caisse especes</span><span class="valeur">{{ $dt($solde) }}</span></td>
    </tr>
</table>

<h2>Charges par categorie</h2>
@if ($chargesParCategorie->isEmpty())
    <p class="vide">Aucune charge sur cette periode.</p>
@else
    <table>
        <thead><tr><th>Categorie</th><th class="num">Nombre</th><th class="num">Total</th></tr></thead>
        <tbody>
        @foreach ($chargesParCategorie as $ligne)
            <tr>
                <td>{{ ucfirst($ligne->categorie) }}</td>
                <td class="num">{{ $ligne->nombre }}</td>
                <td class="num">{{ $dt($ligne->total) }}</td>
            </tr>
        @endforeach
        <tr><th>Total</th><th></th><th class="num">{{ $dt($totalCharges) }}</th></tr>
        </tbody>
    </table>
@endif

<h2>Cout de revient par lot</h2>
@if ($chargesParLot->isEmpty())
    <p class="vide">Aucun lot charge sur cette periode.</p>
@else
    <table>
        <thead><tr><th>Lot</th><th>Modele</th><th class="num">Quantite</th><th class="num">Cout total</th><th class="num">Cout unitaire</th></tr></thead>
        <tbody>
        @foreach ($chargesParLot as $ligne)
            <tr>
                <td>{{ $ligne->numero }}</td>
                <td>{{ $ligne->designation }}</td>
                <td class="num">{{ $ligne->quantite_produite }}</td>
                <td class="num">{{ $dt($ligne->total) }}</td>
                <td class="num">{{ $dt($ligne->cout_unitaire) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>Ventes par modele</h2>
@if ($ventesParModele->isEmpty())
    <p class="vide">Aucune vente validee sur cette periode.</p>
@else
    <table>
        <thead><tr><th>Reference</th><th>Designation</th><th class="num">Quantite</th><th class="num">CA</th><th class="num">Cout</th><th class="num">Marge</th></tr></thead>
        <tbody>
        @foreach ($ventesParModele as $ligne)
            <tr>
                <td>{{ $ligne->reference }}</td>
                <td>{{ $ligne->designation }}</td>
                <td class="num">{{ $ligne->quantite }}</td>
                <td class="num">{{ $dt($ligne->chiffre) }}</td>
                <td class="num">{{ $dt($ligne->cout) }}</td>
                <td class="num">{{ $dt($ligne->marge) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>Ventes par client</h2>
@if ($ventesParClient->isEmpty())
    <p class="vide">Aucune vente validee sur cette periode.</p>
@else
    <table>
        <thead><tr><th>Client</th><th class="num">Factures</th><th class="num">CA</th><th class="num">Cout</th><th class="num">Marge</th></tr></thead>
        <tbody>
        @foreach ($ventesParClient as $ligne)
            <tr>
                <td>{{ $ligne->nom }}</td>
                <td class="num">{{ $ligne->factures }}</td>
                <td class="num">{{ $dt($ligne->chiffre) }}</td>
                <td class="num">{{ $dt($ligne->cout) }}</td>
                <td class="num">{{ $dt($ligne->marge) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>Creances clients</h2>
@if ($creances->isEmpty())
    <p class="vide">Aucune creance en cours.</p>
@else
    <table>
        <thead><tr><th>Client</th><th>Facture</th><th>Date</th><th class="num">Total</th><th class="num">Encaisse</th><th class="num">Reste du</th></tr></thead>
        <tbody>
        @foreach ($creances as $ligne)
            <tr>
                <td>{{ $ligne->nom }}</td>
                <td>{{ $ligne->numero_facture }}</td>
                <td>{{ \Carbon\Carbon::parse($ligne->date_vente)->format('d/m/Y') }}</td>
                <td class="num">{{ $dt($ligne->total) }}</td>
                <td class="num">{{ $dt($ligne->encaisse) }}</td>
                <td class="num">{{ $dt($ligne->reste) }}</td>
            </tr>
        @endforeach
        <tr><th colspan="5">Total des creances</th><th class="num">{{ $dt($creances->sum('reste')) }}</th></tr>
        </tbody>
    </table>
@endif

<h2>Dettes fournisseurs</h2>
@if ($dettes->isEmpty())
    <p class="vide">Aucune dette fournisseur.</p>
@else
    <table>
        <thead><tr><th>Fournisseur</th><th class="num">Engage</th><th class="num">Paye</th><th class="num">Reste a payer</th></tr></thead>
        <tbody>
        @foreach ($dettes as $ligne)
            <tr>
                <td>{{ $ligne->nom }}</td>
                <td class="num">{{ $dt($ligne->engage) }}</td>
                <td class="num">{{ $dt($ligne->paye) }}</td>
                <td class="num">{{ $dt($ligne->reste) }}</td>
            </tr>
        @endforeach
        <tr><th colspan="3">Total des dettes</th><th class="num">{{ $dt($dettes->sum('reste')) }}</th></tr>
        </tbody>
    </table>
@endif

<p class="note">
    Le cout des produits vendus utilise le cout unitaire fige sur chaque ligne de facture au moment
    de sa validation, et non le cout actuel du lot. Le solde de caisse ne comprend que les especes :
    les reglements par virement ou cheque n'y figurent pas.
</p>
</body>
</html>
