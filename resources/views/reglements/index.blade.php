@extends('layouts.app')

@section('title', 'Reglements')

@section('content')
    <h1 class="h3 mb-1">Reglements</h1>
    <p class="text-body-secondary mb-4">
        Encaissements clients et paiements de charges. La saisie se fait depuis la facture
        ou la charge concernee, ou le reste du est connu.
    </p>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card shadow-sm h-100"><div class="card-body">
                <p class="small text-body-secondary mb-1">Encaisse (clients)</p>
                <p class="h5 mb-0 text-success"><x-montant :value="$totalEncaisse"/></p>
                <p class="small text-body-secondary mb-0 mt-1">dont especes : <x-montant :value="$encaisseEspeces"/></p>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100"><div class="card-body">
                <p class="small text-body-secondary mb-1">Paye (fournisseurs)</p>
                <p class="h5 mb-0 text-danger"><x-montant :value="$totalPaye"/></p>
                <p class="small text-body-secondary mb-0 mt-1">dont especes : <x-montant :value="$payeEspeces"/></p>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100"><div class="card-body">
                <form method="GET" action="{{ route('reglements.index') }}" class="row g-2 align-items-end">
                    <div class="col-4">
                        <label for="du" class="form-label small mb-1">Du</label>
                        <input type="date" id="du" name="du" value="{{ $filtres['du'] ?? '' }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-4">
                        <label for="au" class="form-label small mb-1">Au</label>
                        <input type="date" id="au" name="au" value="{{ $filtres['au'] ?? '' }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-4">
                        <label for="mode" class="form-label small mb-1">Mode</label>
                        <select id="mode" name="mode" class="form-select form-select-sm">
                            <option value="">Tous</option>
                            @foreach (['espece' => 'Especes', 'virement' => 'Virement', 'cheque' => 'Cheque'] as $v => $lib)
                                <option value="{{ $v }}" {{ ($filtres['mode'] ?? '') === $v ? 'selected' : '' }}>{{ $lib }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-sm btn-outline-primary">Filtrer</button>
                        <a href="{{ route('reglements.index') }}" class="btn btn-sm btn-link">Tout</a>
                    </div>
                </form>
            </div></div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">Encaissements clients</h2>
            @if ($encaissements->isEmpty())
                <p class="text-body-secondary mb-0">Aucun encaissement sur cette periode.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Date</th><th>Facture</th><th>Client</th><th>Mode</th><th>Reference</th><th>Caisse</th><th class="text-end">Montant</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($encaissements as $r)
                                <tr>
                                    <td>{{ $r->date_reglement->format('d/m/Y') }}</td>
                                    <td><a href="{{ route('ventes.show', $r->vente) }}">{{ $r->vente->numero_facture }}</a></td>
                                    <td>{{ $r->vente->client?->nom ?: 'Comptoir' }}</td>
                                    <td>@include('partials.mode', ['mode' => $r->mode])</td>
                                    <td>{{ $r->reference ?: '—' }}</td>
                                    <td class="small">
                                        @if ($r->mode === 'espece')
                                            <span class="text-success">entree de caisse</span>
                                        @else
                                            <span class="text-body-secondary">hors caisse especes</span>
                                        @endif
                                    </td>
                                    <td class="text-end"><x-montant :value="$r->montant"/></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Paiements de charges</h2>
            @if ($paiements->isEmpty())
                <p class="text-body-secondary mb-0">Aucun paiement sur cette periode.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Date</th><th>Piece</th><th>Fournisseur</th><th>Lot</th><th>Mode</th><th>Caisse</th><th class="text-end">Montant</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($paiements as $p)
                                <tr>
                                    <td>{{ $p->date_paiement->format('d/m/Y') }}</td>
                                    <td><a href="{{ route('charges.show', $p->charge) }}">{{ $p->charge->numero_piece }}</a></td>
                                    <td>{{ $p->charge->fournisseur?->nom ?: '—' }}</td>
                                    <td><a href="{{ route('lots.show', $p->charge->lot) }}">{{ $p->charge->lot->numero }}</a></td>
                                    <td>@include('partials.mode', ['mode' => $p->mode])</td>
                                    <td class="small">
                                        @if ($p->mode === 'espece')
                                            <span class="text-danger">sortie de caisse</span>
                                        @else
                                            <span class="text-body-secondary">hors caisse especes</span>
                                        @endif
                                    </td>
                                    <td class="text-end"><x-montant :value="$p->montant"/></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
