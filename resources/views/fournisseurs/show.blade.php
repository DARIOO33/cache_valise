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
                                <th class="text-end">Montant</th>
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
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-semibold">
                                <td colspan="4" class="text-end">Total engage</td>
                                <td class="text-end"><x-montant :value="$fournisseur->charges->sum('montant')"/></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif

            <p class="text-body-secondary small mb-0">
                Paiements fournisseurs et dette restante : phase 5.
            </p>
        </div>
    </div>

    <p class="mt-3"><a href="{{ route('fournisseurs.index') }}">Retour aux fournisseurs</a></p>
@endsection
