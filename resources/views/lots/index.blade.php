@extends('layouts.app')

@section('title', 'Lots')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Lots de production</h1>
        <a href="{{ route('lots.create') }}" class="btn btn-primary">Creer un lot</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Numero</th>
                        <th>Modele</th>
                        <th>Production</th>
                        <th class="text-end">Produite</th>
                        <th class="text-end">Vendue</th>
                        <th class="text-end">Restante</th>
                        <th class="text-end">Cout total</th>
                        <th class="text-end">Cout unitaire</th>
                        <th class="text-center">Etat</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lots as $lot)
                        @php $vendue = (int) ($lot->quantite_vendue ?? 0); @endphp
                        <tr>
                            <td class="fw-semibold">
                                <a href="{{ route('lots.show', $lot) }}">{{ $lot->numero }}</a>
                            </td>
                            <td>{{ $lot->modele->reference }} — {{ $lot->modele->designation }}</td>
                            <td>{{ $lot->date_production->format('d/m/Y') }}</td>
                            <td class="text-end">{{ $lot->quantite_produite }}</td>
                            <td class="text-end">{{ $vendue }}</td>
                            <td class="text-end fw-semibold">{{ $lot->quantite_produite - $vendue }}</td>
                            <td class="text-end"><x-montant :value="$lot->cout_total ?? 0"/></td>
                            <td class="text-end"><x-montant :value="$lot->quantite_produite > 0 ? round((float) ($lot->cout_total ?? 0) / $lot->quantite_produite, 3) : 0"/></td>
                            <td class="text-center">
                                @include('lots._badge', ['etat' => $lot->etat])
                            </td>
                            <td class="text-end">
                                <a href="{{ route('lots.show', $lot) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                                <a href="{{ route('lots.edit', $lot) }}" class="btn btn-sm btn-outline-secondary">Modifier</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-body-secondary py-4">
                                Aucun lot enregistre.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-body-secondary small mt-3 mb-0">
        Les quantites sont informatives : aucune vente n'est bloquee faute de stock (section 3.2).
    </p>

    <div class="mt-3">{{ $lots->links() }}</div>
@endsection
