@extends('layouts.app')

@section('title', $lot->numero)

@section('content')
    @include('partials.erreurs')

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
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <p class="text-body-secondary small mb-1">Quantite produite</p>
                    <p class="h4 mb-0">{{ $lot->quantite_produite }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <p class="text-body-secondary small mb-1">Vendue / restante</p>
                    <p class="h4 mb-0">{{ $lot->quantite_vendue }} / {{ $lot->quantite_restante }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <p class="text-body-secondary small mb-1">Cout total</p>
                    <p class="h4 mb-0"><x-montant :value="$coutTotal"/></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100 border-primary">
                <div class="card-body">
                    <p class="text-body-secondary small mb-1">Cout unitaire</p>
                    <p class="h4 mb-0 text-primary"><x-montant :value="$coutUnitaire"/></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Charges du lot</h2>
                        <a href="{{ route('charges.create', ['lot_id' => $lot->id]) }}" class="btn btn-sm btn-primary">
                            Ajouter une charge
                        </a>
                    </div>

                    @if ($lot->charges->isEmpty())
                        <p class="text-body-secondary mb-0">
                            Aucune charge enregistree : le cout de revient de ce lot est nul.
                        </p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Piece</th><th>Date</th><th>Categorie</th><th>Designation</th>
                                        <th>Fournisseur</th><th class="text-end">Montant</th><th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lot->charges as $charge)
                                        <tr>
                                            <td>{{ $charge->numero_piece }}</td>
                                            <td>{{ $charge->date_charge->format('d/m/Y') }}</td>
                                            <td>@include('fournisseurs._type', ['type' => $charge->categorie])</td>
                                            <td>{{ $charge->designation }}</td>
                                            <td>{{ $charge->fournisseur?->nom ?: '—' }}</td>
                                            <td class="text-end"><x-montant :value="$charge->montant"/></td>
                                            <td class="text-end">
                                                <a href="{{ route('charges.edit', $charge) }}" class="btn btn-sm btn-outline-secondary">Modifier</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-semibold">
                                        <td colspan="5" class="text-end">Cout total</td>
                                        <td class="text-end"><x-montant :value="$coutTotal"/></td>
                                        <td></td>
                                    </tr>
                                    <tr class="fw-semibold text-primary">
                                        <td colspan="5" class="text-end">
                                            Cout unitaire ({{ $lot->quantite_produite }} unites)
                                        </td>
                                        <td class="text-end"><x-montant :value="$coutUnitaire"/></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h5 mb-3">Repartition par categorie</h2>
                    @if ($repartition->isEmpty())
                        <p class="text-body-secondary mb-0">—</p>
                    @else
                        <table class="table table-sm mb-0">
                            <tbody>
                                @foreach ($repartition as $categorie => $montant)
                                    <tr>
                                        <td>@include('fournisseurs._type', ['type' => $categorie])</td>
                                        <td class="text-end"><x-montant :value="$montant"/></td>
                                        <td class="text-end text-body-secondary small">
                                            {{ $coutTotal > 0 ? round($montant / $coutTotal * 100) : 0 }}%
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5 mb-3">Fiche du lot</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-5">Production</dt>
                        <dd class="col-7">{{ $lot->date_production->format('d/m/Y') }}</dd>

                        <dt class="col-5">Debut / fin</dt>
                        <dd class="col-7">
                            {{ $lot->date_debut?->format('d/m/Y') ?: '—' }} /
                            {{ $lot->date_fin?->format('d/m/Y') ?: '—' }}
                        </dd>

                        <dt class="col-5">Prix conseille</dt>
                        <dd class="col-7"><x-montant :value="$lot->modele->prix_conseille"/></dd>

                        <dt class="col-5">Observations</dt>
                        <dd class="col-7">{{ $lot->observations ?: '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <p class="mt-3"><a href="{{ route('lots.index') }}">Retour aux lots</a></p>
@endsection
