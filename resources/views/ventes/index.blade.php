@extends('layouts.app')

@section('title', 'Ventes')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Ventes et factures</h1>
        <a href="{{ route('ventes.create') }}" class="btn btn-primary">Creer une facture</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card shadow-sm"><div class="card-body">
                <p class="text-body-secondary small mb-1">Chiffre d'affaires (factures validees)</p>
                <p class="h4 mb-0"><x-montant :value="$chiffreAffaires"/></p>
            </div></div>
        </div>
        <div class="col-md-8 d-flex align-items-end">
            <form method="GET" action="{{ route('ventes.index') }}" class="d-flex gap-2">
                <select name="statut" class="form-select form-select-sm">
                    <option value="">Tous les statuts</option>
                    @foreach (['brouillon' => 'Brouillon', 'validee' => 'Validee', 'annulee' => 'Annulee'] as $v => $lib)
                        <option value="{{ $v }}" {{ $statut === $v ? 'selected' : '' }}>{{ $lib }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-outline-primary">Filtrer</button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Facture</th><th>Date</th><th>Client</th><th class="text-center">Statut</th>
                        <th class="text-end">Total</th><th class="text-end">Encaisse</th><th class="text-end">Reste du</th>
                        <th class="text-end">PDF</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ventes as $vente)
                        @php $encaisse = (float) ($vente->encaisse ?? 0); @endphp
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('ventes.show', $vente) }}">{{ $vente->numero_facture }}</a></td>
                            <td>{{ $vente->date_vente->format('d/m/Y') }}</td>
                            <td>
                                @if ($vente->client)
                                    <a href="{{ route('clients.show', $vente->client) }}">{{ $vente->client->nom }}</a>
                                @else
                                    <span class="text-body-secondary">{{ $vente->nom_comptoir ?: 'Comptoir' }}</span>
                                @endif
                            </td>
                            <td class="text-center">@include('ventes._statut', ['statut' => $vente->statut])</td>
                            <td class="text-end"><x-montant :value="$vente->total"/></td>
                            <td class="text-end"><x-montant :value="$encaisse"/></td>
                            <td class="text-end fw-semibold">
                                <x-montant :value="$vente->statut === 'annulee' ? 0 : (float) $vente->total - $encaisse"/>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('ventes.pdf', $vente) }}" class="btn btn-sm btn-outline-secondary">PDF</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-body-secondary py-4">Aucune facture.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $ventes->links() }}</div>
@endsection
