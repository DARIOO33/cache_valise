@extends('layouts.app')

@section('title', 'Fournisseurs')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Fournisseurs et prestataires</h1>
        <a href="{{ route('fournisseurs.create') }}" class="btn btn-primary">Ajouter un fournisseur</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Telephone</th>
                        <th class="text-center">Charges</th>
                        <th class="text-end">Total engage</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fournisseurs as $fournisseur)
                        <tr>
                            <td class="fw-semibold">
                                <a href="{{ route('fournisseurs.show', $fournisseur) }}">{{ $fournisseur->nom }}</a>
                            </td>
                            <td>@include('fournisseurs._type', ['type' => $fournisseur->type])</td>
                            <td>{{ $fournisseur->telephone ?: '—' }}</td>
                            <td class="text-center">{{ $fournisseur->charges_count }}</td>
                            <td class="text-end"><x-montant :value="$fournisseur->total_charges ?? 0"/></td>
                            <td class="text-end">
                                <a href="{{ route('fournisseurs.edit', $fournisseur) }}" class="btn btn-sm btn-outline-secondary">Modifier</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary py-4">Aucun fournisseur enregistre.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $fournisseurs->links() }}</div>
@endsection
