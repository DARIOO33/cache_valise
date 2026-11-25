@extends('layouts.app')

@section('title', $client->nom)

@section('content')
    @include('partials.erreurs')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $client->nom }}</h1>
        <div>
            <a href="{{ route('clients.edit', $client) }}" class="btn btn-outline-secondary">Modifier</a>
            @if ($client->ventes->isEmpty())
                <form method="POST" action="{{ route('clients.destroy', $client) }}" class="d-inline"
                      onsubmit="return confirm('Supprimer ce client ?');">
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
                <p class="text-body-secondary small mb-1">Total facture</p>
                <p class="h4 mb-0"><x-montant :value="$totalFacture"/></p>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100"><div class="card-body">
                <p class="text-body-secondary small mb-1">Encaisse</p>
                <p class="h4 mb-0"><x-montant :value="$totalEncaisse"/></p>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100 {{ $totalFacture - $totalEncaisse > 0 ? 'border-warning' : '' }}">
                <div class="card-body">
                    <p class="text-body-secondary small mb-1">Solde du</p>
                    <p class="h4 mb-0"><x-montant :value="$totalFacture - $totalEncaisse"/></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <dl class="row mb-0 small">
                <dt class="col-sm-3">Telephone</dt><dd class="col-sm-9">{{ $client->telephone ?: '—' }}</dd>
                <dt class="col-sm-3">E-mail</dt><dd class="col-sm-9">{{ $client->email ?: '—' }}</dd>
                <dt class="col-sm-3">Adresse</dt><dd class="col-sm-9">{{ $client->adresse ?: '—' }}</dd>
                <dt class="col-sm-3">Notes</dt><dd class="col-sm-9">{{ $client->notes ?: '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">Historique des ventes</h2>
            @if ($client->ventes->isEmpty())
                <p class="text-body-secondary mb-0">Aucune vente pour ce client.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Facture</th><th>Date</th><th class="text-center">Statut</th>
                                <th class="text-end">Total</th><th class="text-end">Encaisse</th><th class="text-end">Reste</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($client->ventes as $vente)
                                <tr>
                                    <td><a href="{{ route('ventes.show', $vente) }}">{{ $vente->numero_facture }}</a></td>
                                    <td>{{ $vente->date_vente->format('d/m/Y') }}</td>
                                    <td class="text-center">@include('ventes._statut', ['statut' => $vente->statut])</td>
                                    <td class="text-end"><x-montant :value="$vente->total"/></td>
                                    <td class="text-end"><x-montant :value="$vente->encaisse ?? 0"/></td>
                                    <td class="text-end"><x-montant :value="(float) $vente->total - (float) ($vente->encaisse ?? 0)"/></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <p class="mt-3"><a href="{{ route('clients.index') }}">Retour aux clients</a></p>
@endsection
