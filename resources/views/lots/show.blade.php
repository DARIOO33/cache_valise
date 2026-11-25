@extends('layouts.app')

@section('title', $lot->numero)

@section('content')
    @php $vendue = (int) ($lot->quantite_vendue ?? 0); @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Lot {{ $lot->numero }}</h1>
            <p class="text-body-secondary mb-0">
                {{ $lot->modele->reference }} — {{ $lot->modele->designation }}
            </p>
        </div>
        <div>
            @include('lots._badge', ['etat' => $lot->etat])
            <a href="{{ route('lots.edit', $lot) }}" class="btn btn-outline-secondary ms-2">Modifier</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <p class="text-body-secondary small mb-1">Quantite produite</p>
                    <p class="h4 mb-0">{{ $lot->quantite_produite }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <p class="text-body-secondary small mb-1">Quantite vendue</p>
                    <p class="h4 mb-0">{{ $vendue }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <p class="text-body-secondary small mb-1">Quantite restante</p>
                    <p class="h4 mb-0">{{ $lot->quantite_produite - $vendue }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Fiche du lot</h2>
            <dl class="row mb-0">
                <dt class="col-sm-3">Date de production</dt>
                <dd class="col-sm-9">{{ $lot->date_production->format('d/m/Y') }}</dd>

                <dt class="col-sm-3">Debut / fin</dt>
                <dd class="col-sm-9">
                    {{ $lot->date_debut?->format('d/m/Y') ?: '—' }} /
                    {{ $lot->date_fin?->format('d/m/Y') ?: '—' }}
                </dd>

                <dt class="col-sm-3">Prix conseille du modele</dt>
                <dd class="col-sm-9"><x-montant :value="$lot->modele->prix_conseille"/></dd>

                <dt class="col-sm-3">Observations</dt>
                <dd class="col-sm-9">{{ $lot->observations ?: '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body text-body-secondary">
            <h2 class="h5 mb-2 text-body">Charges et cout de revient</h2>
            <p class="mb-0 small">Disponible en phase 3 : saisie des charges, cout total et cout unitaire du lot.</p>
        </div>
    </div>

    <p class="mt-3"><a href="{{ route('lots.index') }}">Retour aux lots</a></p>
@endsection
