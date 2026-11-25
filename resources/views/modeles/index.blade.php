@extends('layouts.app')

@section('title', 'Modeles')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Modeles de cache-valises</h1>
        <a href="{{ route('modeles.create') }}" class="btn btn-primary">Ajouter un modele</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Reference</th>
                        <th>Designation</th>
                        <th>Dimensions</th>
                        <th class="text-end">Prix conseille</th>
                        <th class="text-center">Lots</th>
                        <th class="text-center">Etat</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($modeles as $modele)
                        <tr class="{{ $modele->actif ? '' : 'text-body-secondary' }}">
                            <td class="fw-semibold">{{ $modele->reference }}</td>
                            <td>{{ $modele->designation }}</td>
                            <td>{{ $modele->dimensions ?: '—' }}</td>
                            <td class="text-end"><x-montant :value="$modele->prix_conseille"/></td>
                            <td class="text-center">{{ $modele->lots_count }}</td>
                            <td class="text-center">
                                @if ($modele->actif)
                                    <span class="badge text-bg-success">Actif</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('modeles.edit', $modele) }}" class="btn btn-sm btn-outline-secondary">Modifier</a>
                                <form method="POST" action="{{ route('modeles.activation', $modele) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $modele->actif ? 'warning' : 'success' }}">
                                        {{ $modele->actif ? 'Desactiver' : 'Activer' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-body-secondary py-4">
                                Aucun modele enregistre.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $modeles->links() }}</div>
@endsection
