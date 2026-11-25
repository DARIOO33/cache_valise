@extends('layouts.app')

@section('title', 'Rapports')

@php
    $periode = array_filter(['du' => $du, 'au' => $au]);
    $lien = fn (string $rapport) => route('rapports.csv', $periode + ['rapport' => $rapport]);
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Rapports</h1>
        <a href="{{ route('rapports.pdf', $periode) }}" class="btn btn-primary">Exporter en PDF</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('rapports.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="du" class="form-label small mb-1">Du</label>
                    <input type="date" id="du" name="du" value="{{ $du }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label for="au" class="form-label small mb-1">Au</label>
                    <input type="date" id="au" name="au" value="{{ $au }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-6">
                    <button class="btn btn-sm btn-outline-primary">Appliquer</button>
                    <a href="{{ route('rapports.index') }}" class="btn btn-sm btn-link">Toutes periodes</a>
                    @if ($du || $au)
                        <span class="text-body-secondary small ms-2">
                            Periode : {{ $du ?: 'origine' }} au {{ $au ?: "aujourd'hui" }}
                        </span>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm h-100"><div class="card-body">
                <p class="small text-body-secondary mb-1">Chiffre d'affaires</p>
                <p class="h5 mb-0"><x-montant :value="$chiffreAffaires"/></p>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100"><div class="card-body">
                <p class="small text-body-secondary mb-1">Cout des produits vendus</p>
                <p class="h5 mb-0"><x-montant :value="$cogs"/></p>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100 border-success"><div class="card-body">
                <p class="small text-body-secondary mb-1">Marge brute</p>
                <p class="h5 mb-0 text-success"><x-montant :value="$marge"/></p>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100"><div class="card-body">
                <p class="small text-body-secondary mb-1">Charges engagees</p>
                <p class="h5 mb-0"><x-montant :value="$totalCharges"/></p>
            </div></div>
        </div>
    </div>

    @include('rapports._section', [
        'titre' => 'Charges par categorie',
        'csv' => $lien('charges-categorie'),
        'vide' => $chargesParCategorie->isEmpty(),
        'entetes' => ['Categorie', 'Nombre', 'Total'],
        'lignes' => $chargesParCategorie->map(fn ($r) => [$r->categorie, $r->nombre, ['montant' => $r->total]]),
    ])

    @include('rapports._section', [
        'titre' => 'Cout de revient par lot',
        'csv' => $lien('charges-lot'),
        'vide' => $chargesParLot->isEmpty(),
        'entetes' => ['Lot', 'Modele', 'Quantite', 'Cout total', 'Cout unitaire'],
        'lignes' => $chargesParLot->map(fn ($r) => [
            $r->numero, $r->designation, $r->quantite_produite,
            ['montant' => $r->total], ['montant' => $r->cout_unitaire],
        ]),
    ])

    @include('rapports._section', [
        'titre' => 'Ventes par modele',
        'csv' => $lien('ventes-modele'),
        'vide' => $ventesParModele->isEmpty(),
        'entetes' => ['Reference', 'Designation', 'Quantite', 'Chiffre affaires', 'Cout', 'Marge'],
        'lignes' => $ventesParModele->map(fn ($r) => [
            $r->reference, $r->designation, $r->quantite,
            ['montant' => $r->chiffre], ['montant' => $r->cout], ['montant' => $r->marge],
        ]),
    ])

    @include('rapports._section', [
        'titre' => 'Creances clients',
        'csv' => $lien('creances'),
        'vide' => $creances->isEmpty(),
        'entetes' => ['Client', 'Facture', 'Date', 'Total', 'Encaisse', 'Reste du'],
        'lignes' => $creances->map(fn ($r) => [
            $r->nom, $r->numero_facture, \Carbon\Carbon::parse($r->date_vente)->format('d/m/Y'),
            ['montant' => $r->total], ['montant' => $r->encaisse], ['montant' => $r->reste],
        ]),
    ])

    @include('rapports._section', [
        'titre' => 'Dettes fournisseurs',
        'csv' => $lien('dettes'),
        'vide' => $dettes->isEmpty(),
        'entetes' => ['Fournisseur', 'Engage', 'Paye', 'Reste a payer'],
        'lignes' => $dettes->map(fn ($r) => [
            $r->nom, ['montant' => $r->engage], ['montant' => $r->paye], ['montant' => $r->reste],
        ]),
    ])

    <div class="card shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h6 mb-1">Journal de caisse</h2>
                <p class="text-body-secondary small mb-0">
                    Solde especes actuel : <x-montant :value="$solde"/>
                </p>
            </div>
            <div>
                <a href="{{ route('caisse.index') }}" class="btn btn-sm btn-outline-secondary">Consulter</a>
                <a href="{{ $lien('caisse') }}" class="btn btn-sm btn-outline-secondary">CSV</a>
            </div>
        </div>
    </div>

    <p class="text-body-secondary small mt-3 mb-0">
        Le cout des produits vendus utilise le cout unitaire fige sur chaque ligne de facture,
        jamais le cout actuel du lot.
    </p>
@endsection
