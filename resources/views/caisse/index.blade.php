@extends('layouts.app')

@section('title', 'Caisse')

@section('content')
    @include('partials.erreurs')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Journal de caisse</h1>
        <a href="{{ route('caisse.create') }}" class="btn btn-primary">Nouveau mouvement</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card shadow-sm border-primary"><div class="card-body">
                <p class="text-body-secondary small mb-1">Solde caisse especes</p>
                <p class="h3 mb-0 text-primary"><x-montant :value="$solde"/></p>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100"><div class="card-body">
                <p class="text-body-secondary small mb-1">Entrees (periode affichee)</p>
                <p class="h4 mb-0 text-success"><x-montant :value="$totalEntrees"/></p>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100"><div class="card-body">
                <p class="text-body-secondary small mb-1">Sorties (periode affichee)</p>
                <p class="h4 mb-0 text-danger"><x-montant :value="$totalSorties"/></p>
            </div></div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('caisse.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="f_du" class="form-label small mb-1">Du</label>
                    <input type="date" id="f_du" name="du" value="{{ $filtres['du'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label for="f_au" class="form-label small mb-1">Au</label>
                    <input type="date" id="f_au" name="au" value="{{ $filtres['au'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-4">
                    <label for="f_type" class="form-label small mb-1">Type</label>
                    <select id="f_type" name="type" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach (['solde_initial' => 'Solde initial', 'alimentation' => 'Alimentation', 'reglement_client' => 'Reglement client', 'paiement_charge' => 'Paiement charge', 'retrait' => 'Retrait', 'depot_bancaire' => 'Depot bancaire', 'correction' => 'Correction'] as $v => $lib)
                            <option value="{{ $v }}" {{ ($filtres['type'] ?? '') === $v ? 'selected' : '' }}>{{ $lib }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-outline-primary">Filtrer</button>
                    <a href="{{ route('caisse.index') }}" class="btn btn-sm btn-link">Tout</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th><th>Type</th><th>Libelle</th><th>Origine</th>
                        <th class="text-end">Entree</th><th class="text-end">Sortie</th>
                        <th class="text-end">Solde</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mouvements as $m)
                        <tr class="{{ $m->annule_mouvement_id ? 'table-warning' : '' }}">
                            <td>{{ $m->date_mouvement->format('d/m/Y') }}</td>
                            <td>@include('caisse._type', ['type' => $m->type])</td>
                            <td>{{ $m->libelle }}</td>
                            <td class="small">
                                @if ($m->reglement)
                                    <a href="{{ route('ventes.show', $m->reglement->vente) }}">{{ $m->reglement->vente->numero_facture }}</a>
                                @elseif ($m->paiementCharge)
                                    <a href="{{ route('charges.show', $m->paiementCharge->charge) }}">{{ $m->paiementCharge->charge->numero_piece }}</a>
                                @else
                                    <span class="text-body-secondary">{{ $m->reference ?: '—' }}</span>
                                @endif
                            </td>
                            <td class="text-end text-success">
                                @if ($m->sens === 'entree')<x-montant :value="$m->montant"/>@endif
                            </td>
                            <td class="text-end text-danger">
                                @if ($m->sens === 'sortie')<x-montant :value="$m->montant"/>@endif
                            </td>
                            <td class="text-end fw-semibold"><x-montant :value="$m->solde_cumule"/></td>
                            <td class="text-end">
                                @if (! $m->annule_mouvement_id && $m->contreEcriture()->doesntExist())
                                    <form method="POST" action="{{ route('caisse.annulation', $m) }}"
                                          onsubmit="return confirm('Ecrire la contre-ecriture de ce mouvement ?');">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger">Annuler</button>
                                    </form>
                                @elseif ($m->annule_mouvement_id)
                                    <span class="text-body-secondary small">contre-ecriture</span>
                                @else
                                    <span class="text-body-secondary small">annule</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-body-secondary py-4">Aucun mouvement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-body-secondary small mt-3 mb-0">
        Le journal est en ecriture seule : un mouvement errone est annule par une contre-ecriture,
        jamais supprime. Les reglements par virement ou cheque n'y figurent pas.
    </p>
@endsection
