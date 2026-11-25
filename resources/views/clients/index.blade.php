@extends('layouts.app')

@section('title', 'Clients')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Clients</h1>
        <a href="{{ route('clients.create') }}" class="btn btn-primary">Ajouter un client</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nom</th><th>Telephone</th><th>E-mail</th>
                        <th class="text-center">Factures</th><th class="text-end">Total facture</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clients as $client)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('clients.show', $client) }}">{{ $client->nom }}</a></td>
                            <td>{{ $client->telephone ?: '—' }}</td>
                            <td>{{ $client->email ?: '—' }}</td>
                            <td class="text-center">{{ $client->ventes_count }}</td>
                            <td class="text-end"><x-montant :value="$client->total_facture ?? 0"/></td>
                            <td class="text-end">
                                <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-secondary">Modifier</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">Aucun client enregistre.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-body-secondary small mt-3 mb-0">
        Une vente comptoir n'a pas de client nominatif et n'apparait pas ici.
    </p>

    <div class="mt-3">{{ $clients->links() }}</div>
@endsection
