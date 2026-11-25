@extends('layouts.app')

@section('title', 'Charges')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Charges de fabrication</h1>
        <a href="{{ route('charges.create') }}" class="btn btn-primary">Ajouter une charge</a>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('charges.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="f_lot" class="form-label small mb-1">Lot</label>
                    <select id="f_lot" name="lot_id" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach ($lots as $l)
                            <option value="{{ $l->id }}" {{ (int) ($filtres['lot_id'] ?? 0) === $l->id ? 'selected' : '' }}>
                                {{ $l->numero }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="f_cat" class="form-label small mb-1">Categorie</label>
                    <select id="f_cat" name="categorie" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        @foreach (['tissu' => 'Tissu', 'couture' => 'Couture', 'impression' => 'Impression', 'autre' => 'Autre'] as $v => $lib)
                            <option value="{{ $v }}" {{ ($filtres['categorie'] ?? '') === $v ? 'selected' : '' }}>{{ $lib }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="f_du" class="form-label small mb-1">Du</label>
                    <input type="date" id="f_du" name="du" value="{{ $filtres['du'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label for="f_au" class="form-label small mb-1">Au</label>
                    <input type="date" id="f_au" name="au" value="{{ $filtres['au'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-outline-primary">Filtrer</button>
                    <a href="{{ route('charges.index') }}" class="btn btn-sm btn-link">Reinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Piece</th><th>Date</th><th>Lot</th><th>Categorie</th>
                        <th>Designation</th><th>Fournisseur</th>
                        <th class="text-end">Qte</th><th class="text-end">P.U.</th>
                        <th class="text-end">Montant</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($charges as $charge)
                        <tr>
                            <td class="fw-semibold">{{ $charge->numero_piece }}</td>
                            <td>{{ $charge->date_charge->format('d/m/Y') }}</td>
                            <td><a href="{{ route('lots.show', $charge->lot) }}">{{ $charge->lot->numero }}</a></td>
                            <td>@include('fournisseurs._type', ['type' => $charge->categorie])</td>
                            <td>{{ $charge->designation }}</td>
                            <td>
                                @if ($charge->fournisseur)
                                    <a href="{{ route('fournisseurs.show', $charge->fournisseur) }}">{{ $charge->fournisseur->nom }}</a>
                                @else
                                    <span class="text-body-secondary">—</span>
                                @endif
                            </td>
                            <td class="text-end">{{ rtrim(rtrim(number_format((float) $charge->quantite, 3, ',', ' '), '0'), ',') }} {{ $charge->unite }}</td>
                            <td class="text-end"><x-montant :value="$charge->prix_unitaire"/></td>
                            <td class="text-end fw-semibold"><x-montant :value="$charge->montant"/></td>
                            <td class="text-end">
                                <a href="{{ route('charges.edit', $charge) }}" class="btn btn-sm btn-outline-secondary">Modifier</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-body-secondary py-4">Aucune charge.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-semibold table-light">
                        <td colspan="8" class="text-end">Total {{ array_filter($filtres) ? '(filtre)' : 'general' }}</td>
                        <td class="text-end"><x-montant :value="$total"/></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $charges->links() }}</div>
@endsection
