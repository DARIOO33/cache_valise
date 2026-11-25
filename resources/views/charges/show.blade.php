@extends('layouts.app')

@section('title', $charge->numero_piece)

@section('content')
    @include('partials.erreurs')

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h3 mb-1">Charge {{ $charge->numero_piece }}</h1>
            <p class="text-body-secondary mb-0">
                {{ $charge->date_charge->format('d/m/Y') }} &middot;
                lot <a href="{{ route('lots.show', $charge->lot) }}">{{ $charge->lot->numero }}</a>
            </p>
        </div>
        <a href="{{ route('charges.edit', $charge) }}" class="btn btn-outline-secondary">Modifier</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100"><div class="card-body">
                <p class="text-body-secondary small mb-1">Montant de la charge</p>
                <p class="h4 mb-0"><x-montant :value="$charge->montant"/></p>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100"><div class="card-body">
                <p class="text-body-secondary small mb-1">Deja paye</p>
                <p class="h4 mb-0"><x-montant :value="$charge->paiements->sum('montant')"/></p>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100 {{ $resteAPayer > 0 ? 'border-warning' : 'border-success' }}">
                <div class="card-body">
                    <p class="text-body-secondary small mb-1">Reste a payer</p>
                    <p class="h4 mb-0"><x-montant :value="$resteAPayer"/></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <dl class="row mb-0 small">
                <dt class="col-sm-3">Categorie</dt>
                <dd class="col-sm-9">@include('fournisseurs._type', ['type' => $charge->categorie])</dd>
                <dt class="col-sm-3">Designation</dt><dd class="col-sm-9">{{ $charge->designation }}</dd>
                <dt class="col-sm-3">Quantite</dt>
                <dd class="col-sm-9">{{ rtrim(rtrim(number_format((float) $charge->quantite, 3, ',', ' '), '0'), ',') }} {{ $charge->unite }}</dd>
                <dt class="col-sm-3">Prix unitaire</dt><dd class="col-sm-9"><x-montant :value="$charge->prix_unitaire"/></dd>
                <dt class="col-sm-3">Fournisseur</dt>
                <dd class="col-sm-9">
                    @if ($charge->fournisseur)
                        <a href="{{ route('fournisseurs.show', $charge->fournisseur) }}">{{ $charge->fournisseur->nom }}</a>
                    @else — @endif
                </dd>
                <dt class="col-sm-3">Notes</dt><dd class="col-sm-9">{{ $charge->notes ?: '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Paiements</h2>

            @if ($charge->paiements->isEmpty())
                <p class="text-body-secondary">Aucun paiement enregistre.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr><th>Date</th><th>Mode</th><th>Reference</th><th>Caisse</th><th class="text-end">Montant</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($charge->paiements as $paiement)
                                <tr>
                                    <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                                    <td>@include('partials.mode', ['mode' => $paiement->mode])</td>
                                    <td>{{ $paiement->reference ?: '—' }}</td>
                                    <td>
                                        @if ($paiement->mouvementCaisse)
                                            <span class="text-success small">sortie de caisse</span>
                                        @else
                                            <span class="text-body-secondary small">hors caisse especes</span>
                                        @endif
                                    </td>
                                    <td class="text-end"><x-montant :value="$paiement->montant"/></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($resteAPayer > 0)
                <hr>
                <h3 class="h6 mb-3">Enregistrer un paiement</h3>
                <form method="POST" action="{{ route('charges.paiements.store', $charge) }}">
                    @csrf
                    @include('partials.formulaire-paiement', ['champDate' => 'date_paiement', 'maximum' => $resteAPayer])
                </form>
            @else
                <p class="text-success mb-0">Cette charge est entierement payee.</p>
            @endif
        </div>
    </div>

    <p><a href="{{ route('charges.index') }}">Retour aux charges</a></p>
@endsection
