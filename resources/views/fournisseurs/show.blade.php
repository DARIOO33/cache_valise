@extends('layouts.app')

@section('title', $fournisseur->nom)

@section('content')
    @include('partials.erreurs')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $fournisseur->nom }}</h1>
            <p class="mb-0">@include('fournisseurs._type', ['type' => $fournisseur->type])</p>
        </div>
        <div>
            <a href="{{ route('fournisseurs.edit', $fournisseur) }}" class="btn btn-outline-secondary">Modifier</a>
            @if ($fournisseur->charges->isEmpty())
                <form method="POST" action="{{ route('fournisseurs.destroy', $fournisseur) }}" class="d-inline"
                      onsubmit="return confirm('Supprimer ce fournisseur ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">Supprimer</button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100"><div class="card-body">
                <p class="text-body-secondary small mb-1">Total engage</p>
                <p class="h4 mb-0"><x-montant :value="$engage"/></p>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100"><div class="card-body">
                <p class="text-body-secondary small mb-1">Deja paye</p>
                <p class="h4 mb-0"><x-montant :value="$paye"/></p>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100 {{ $dette > 0 ? 'border-warning' : '' }}"><div class="card-body">
                <p class="text-body-secondary small mb-1">Dette restante</p>
                <p class="h4 mb-0"><x-montant :value="$dette"/></p>
            </div></div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Telephone</dt><dd class="col-sm-9">{{ $fournisseur->telephone ?: '—' }}</dd>
                <dt class="col-sm-3">E-mail</dt><dd class="col-sm-9">{{ $fournisseur->email ?: '—' }}</dd>
                <dt class="col-sm-3">Adresse</dt><dd class="col-sm-9">{{ $fournisseur->adresse ?: '—' }}</dd>
                <dt class="col-sm-3">Notes</dt><dd class="col-sm-9">{{ $fournisseur->notes ?: '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">Historique des charges</h2>

            @if ($fournisseur->charges->isEmpty())
                <p class="text-body-secondary mb-0">Aucune charge enregistree pour ce fournisseur.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-3">
                        <thead class="table-light">
                            <tr>
                                <th>Piece</th><th>Date</th><th>Lot</th><th>Designation</th>
                                <th class="text-end">Montant</th><th class="text-end">Reste</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($fournisseur->charges as $charge)
                                <tr>
                                    <td>{{ $charge->numero_piece }}</td>
                                    <td>{{ $charge->date_charge->format('d/m/Y') }}</td>
                                    <td><a href="{{ route('lots.show', $charge->lot) }}">{{ $charge->lot->numero }}</a></td>
                                    <td>{{ $charge->designation }}</td>
                                    <td class="text-end"><x-montant :value="$charge->montant"/></td>
                                    <td class="text-end">
                                        <x-montant :value="(float) $charge->montant - (float) $charge->paiements->sum('montant')"/>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-semibold">
                                <td colspan="4" class="text-end">Total engage</td>
                                <td class="text-end"><x-montant :value="$engage"/></td>
                                <td class="text-end"><x-montant :value="$dette"/></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif

        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">Historique des paiements</h2>

            @if ($paiements->isEmpty())
                <p class="text-body-secondary mb-0">Aucun paiement enregistre pour ce fournisseur.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th><th>Piece</th><th>Mode</th><th>Reference</th>
                                <th>Caisse</th><th class="text-end">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($paiements as $paiement)
                                <tr>
                                    <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                                    <td>
                                        <a href="{{ route('charges.show', $paiement->charge_id) }}">
                                            {{ $paiement->charge->numero_piece ?? '#'.$paiement->charge_id }}
                                        </a>
                                    </td>
                                    <td>@include('partials.mode', ['mode' => $paiement->mode])</td>
                                    <td>{{ $paiement->reference ?: '—' }}</td>
                                    <td class="small">
                                        @if ($paiement->mode === 'espece')
                                            <span class="text-success">sortie de caisse</span>
                                        @else
                                            <span class="text-body-secondary">hors caisse especes</span>
                                        @endif
                                    </td>
                                    <td class="text-end"><x-montant :value="$paiement->montant"/></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-semibold">
                                <td colspan="5" class="text-end">Total paye</td>
                                <td class="text-end"><x-montant :value="$paye"/></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <p class="mt-3"><a href="{{ route('fournisseurs.index') }}">Retour aux fournisseurs</a></p>
@endsection
