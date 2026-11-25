@extends('layouts.app')

@section('title', 'Tableau de bord')

@push('styles')
<style>
    .viz-root {
        --surface-1: #ffffff;
        --text-secondary: #666666;
        --grid: #e9ecef;
        --series-1: #257de0;
    }
    .kpi-label { font-size: .8125rem; color: var(--text-secondary); }
    /* maintainAspectRatio:false sizes the canvas to its parent, so the parent
       must have a fixed height - otherwise canvas and parent grow each other. */
    .graphique { position: relative; height: 260px; }
    .graphique canvas { display: block; }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Tableau de bord</h1>
            <p class="text-body-secondary mb-0">{{ now()->translatedFormat('F Y') }}</p>
        </div>
        <a href="{{ route('rapports.index') }}" class="btn btn-outline-primary">Rapports detailles</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm h-100"><div class="card-body">
                <p class="kpi-label mb-1">Chiffre d'affaires du mois</p>
                <p class="h4 mb-0"><x-montant :value="$caMois"/></p>
                <p class="kpi-label mb-0 mt-1">Total : <x-montant :value="$caTotal"/></p>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100"><div class="card-body">
                <p class="kpi-label mb-1">Marge brute du mois</p>
                <p class="h4 mb-0 text-success"><x-montant :value="$margeMois"/></p>
                <p class="kpi-label mb-0 mt-1">Total : <x-montant :value="$margeTotale"/></p>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100"><div class="card-body">
                <p class="kpi-label mb-1">Charges du mois</p>
                <p class="h4 mb-0"><x-montant :value="$chargesMois"/></p>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100 border-primary"><div class="card-body">
                <p class="kpi-label mb-1">Solde caisse especes</p>
                <p class="h4 mb-0 text-primary"><x-montant :value="$solde"/></p>
                <p class="kpi-label mb-0 mt-1">
                    <a href="{{ route('caisse.index') }}">Journal</a>
                </p>
            </div></div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100 {{ $creances > 0 ? 'border-warning' : '' }}"><div class="card-body">
                <p class="kpi-label mb-1">Creances clients (reste du)</p>
                <p class="h4 mb-0"><x-montant :value="$creances"/></p>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100 {{ $dettes > 0 ? 'border-warning' : '' }}"><div class="card-body">
                <p class="kpi-label mb-1">Dettes fournisseurs (reste a payer)</p>
                <p class="h4 mb-0"><x-montant :value="$dettes"/></p>
            </div></div>
        </div>
    </div>

    <div class="row g-3 viz-root">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-1">Chiffre d'affaires par mois</h2>
                    <p class="kpi-label mb-3">Factures validees, 12 derniers mois, en DT</p>
                    <div class="graphique">
                        <canvas id="graphVentes"
                                aria-label="Chiffre d'affaires mensuel des douze derniers mois"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-1">Charges par categorie</h2>
                    <p class="kpi-label mb-3">Toutes periodes, en DT</p>
                    @if ($chargesParCategorie->isEmpty())
                        <p class="text-body-secondary mb-0">Aucune charge enregistree.</p>
                    @else
                        <div class="graphique">
                            <canvas id="graphCharges"
                                    aria-label="Repartition des charges par categorie"></canvas>
                        </div>
                    @endif
                    <p class="kpi-label mt-3 mb-0">
                        <a href="{{ route('rapports.index') }}">Voir les chiffres en tableau</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const styles = getComputedStyle(document.querySelector('.viz-root'));
        const serie = styles.getPropertyValue('--series-1').trim();
        const grille = styles.getPropertyValue('--grid').trim();
        const texte = styles.getPropertyValue('--text-secondary').trim();

        const dinars = (valeur) => valeur.toLocaleString('fr-FR', {
            minimumFractionDigits: 3,
            maximumFractionDigits: 3,
        }) + ' DT';

        // Recessive axes, one measure, one scale: never a second y-axis here.
        const axes = {
            x: { grid: { display: false }, border: { color: grille }, ticks: { color: texte } },
            y: {
                beginAtZero: true,
                grid: { color: grille },
                border: { display: false },
                ticks: { color: texte, callback: (v) => v.toLocaleString('fr-FR') },
            },
        };

        const infobulle = {
            callbacks: { label: (ctx) => dinars(ctx.parsed.y ?? ctx.parsed.x) },
        };

        new Chart(document.getElementById('graphVentes'), {
            type: 'line',
            data: {
                labels: @json($ventesParMois->pluck('libelle')),
                datasets: [{
                    data: @json($ventesParMois->pluck('total')),
                    borderColor: serie,
                    backgroundColor: serie,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false }, tooltip: infobulle },
                scales: axes,
            },
        });

        const canvasCharges = document.getElementById('graphCharges');

        if (canvasCharges) {
            new Chart(canvasCharges, {
                type: 'bar',
                data: {
                    labels: @json($chargesParCategorie->pluck('categorie')),
                    datasets: [{
                        data: @json($chargesParCategorie->pluck('total')),
                        backgroundColor: serie,
                        borderRadius: 4,
                        barThickness: 18,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: infobulle },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: grille },
                            border: { display: false },
                            ticks: { color: texte, callback: (v) => v.toLocaleString('fr-FR') },
                        },
                        y: { grid: { display: false }, border: { color: grille }, ticks: { color: texte } },
                    },
                },
            });
        }
    })();
</script>
@endpush
