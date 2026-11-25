@extends('layouts.app')

@section('title', $vente->numero_facture)

@section('content')
    @include('partials.erreurs')

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h3 mb-1">Facture {{ $vente->numero_facture }}</h1>
            <p class="text-body-secondary mb-0">
                {{ $vente->date_vente->format('d/m/Y') }} &middot;
                @if ($vente->client)
                    <a href="{{ route('clients.show', $vente->client) }}">{{ $vente->client->nom }}</a>
                @else
                    Vente comptoir{{ $vente->nom_comptoir ? ' — '.$vente->nom_comptoir : '' }}
                @endif
            </p>
        </div>
        <div class="text-end">
            <div class="mb-2">@include('ventes._statut', ['statut' => $vente->statut])</div>
            @if ($vente->statut === 'brouillon')
                <a href="{{ route('ventes.edit', $vente) }}" class="btn btn-sm btn-outline-secondary">Modifier l'en-tete</a>
                <form method="POST" action="{{ route('ventes.validation', $vente) }}" class="d-inline"
                      onsubmit="return confirm('Valider la facture ? Le cout unitaire des lignes sera fige.');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success">Valider la facture</button>
                </form>
            @endif
            @if ($vente->statut !== 'annulee')
                <form method="POST" action="{{ route('ventes.annulation', $vente) }}" class="d-inline"
                      onsubmit="return confirm('Annuler toute la facture ? Cette action est definitive.');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger">Annuler la facture</button>
                </form>
            @endif
        </div>
    </div>

    @if ($lotsEnPreparation->isNotEmpty())
        <div class="alert alert-warning">
            <strong>Avertissement.</strong>
            {{ $lotsEnPreparation->count() === 1 ? 'Le lot' : 'Les lots' }}
            {{ $lotsEnPreparation->join(', ') }}
            {{ $lotsEnPreparation->count() === 1 ? "n'est" : 'ne sont' }} pas encore
            {{ $lotsEnPreparation->count() === 1 ? 'termine' : 'termines' }}.
            La vente reste autorisee (section 3.2).
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Lignes de la facture</h2>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Designation</th><th>Lot</th>
                            <th class="text-end">Qte</th><th class="text-end">P.U.</th>
                            <th class="text-end">Remise</th><th class="text-end">Montant</th>
                            @if ($vente->statut !== 'brouillon')
                                <th class="text-end">Cout unit. fige</th>
                            @endif
                            @if ($vente->statut === 'brouillon')
                                <th></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vente->lignes as $ligne)
                            <tr>
                                <td>{{ $ligne->designation }}</td>
                                <td>
                                    <a href="{{ route('lots.show', $ligne->lot) }}">{{ $ligne->lot->numero }}</a>
                                    @if ($ligne->lot->etat === 'en_preparation')
                                        <span class="badge text-bg-warning">en preparation</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ $ligne->quantite }}</td>
                                <td class="text-end"><x-montant :value="$ligne->prix_unitaire"/></td>
                                <td class="text-end">
                                    {{ rtrim(rtrim(number_format((float) $ligne->remise_pourcent, 2, ',', ' '), '0'), ',') }}%
                                    @if ((float) $ligne->remise_montant > 0)
                                        <span class="text-body-secondary small d-block">
                                            −<x-montant :value="$ligne->remise_montant"/>
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold"><x-montant :value="$ligne->montant_ligne"/></td>
                                @if ($vente->statut !== 'brouillon')
                                    <td class="text-end text-body-secondary"><x-montant :value="$ligne->cout_unitaire ?? 0"/></td>
                                @endif
                                @if ($vente->statut === 'brouillon')
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('ventes.lignes.destroy', [$vente, $ligne]) }}"
                                              onsubmit="return confirm('Supprimer cette ligne ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Retirer</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-body-secondary py-3">
                                    Aucune ligne. Une facture sans ligne ne peut pas etre validee.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-end">Sous-total</td>
                            <td class="text-end"><x-montant :value="$vente->sous_total"/></td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="5" class="text-end">Remises</td>
                            <td class="text-end">−<x-montant :value="$vente->total_remise"/></td>
                            <td colspan="2"></td>
                        </tr>
                        <tr class="fw-semibold fs-5">
                            <td colspan="5" class="text-end">Total a payer</td>
                            <td class="text-end"><x-montant :value="$vente->total"/></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    @if ($vente->statut === 'brouillon')
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Ajouter une ligne</h2>
                <form method="POST" action="{{ route('ventes.lignes.store', $vente) }}" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label for="lot_id" class="form-label">Lot d'origine</label>
                        <select id="lot_id" name="lot_id" class="form-select" required>
                            <option value="">— Choisir un lot —</option>
                            @foreach ($lots as $l)
                                <option value="{{ $l->id }}" data-prix="{{ $l->modele->prix_conseille }}">
                                    {{ $l->numero }} — {{ $l->modele->designation }}{{ $l->etat === 'en_preparation' ? ' (en preparation)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="quantite" class="form-label">Quantite</label>
                        <input type="number" id="quantite" name="quantite" min="1" step="1" value="1" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label for="prix_unitaire" class="form-label">Prix unitaire</label>
                        <input type="number" id="prix_unitaire" name="prix_unitaire" min="0" step="0.001" value="0.000" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label for="remise_pourcent" class="form-label">Remise (%)</label>
                        <input type="number" id="remise_pourcent" name="remise_pourcent" min="0" max="100" step="0.01" value="0" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
        <script>
            document.getElementById('lot_id').addEventListener('change', function () {
                const prix = this.selectedOptions[0]?.dataset.prix;
                if (prix) {
                    document.getElementById('prix_unitaire').value = parseFloat(prix).toFixed(3);
                }
            });
        </script>
        @endpush
    @endif

    @if ($vente->statut === 'validee')
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm h-100"><div class="card-body">
                    <p class="text-body-secondary small mb-1">Chiffre d'affaires</p>
                    <p class="h5 mb-0"><x-montant :value="$vente->total"/></p>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100"><div class="card-body">
                    <p class="text-body-secondary small mb-1">Cout des produits vendus</p>
                    <p class="h5 mb-0"><x-montant :value="$cogs"/></p>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100 border-success"><div class="card-body">
                    <p class="text-body-secondary small mb-1">Marge brute</p>
                    <p class="h5 mb-0 text-success"><x-montant :value="$marge"/></p>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100 {{ (float) $vente->total - $encaisse > 0 ? 'border-warning' : '' }}">
                    <div class="card-body">
                        <p class="text-body-secondary small mb-1">Reste du</p>
                        <p class="h5 mb-0"><x-montant :value="(float) $vente->total - $encaisse"/></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body text-body-secondary">
                <h2 class="h5 mb-2 text-body">Reglements</h2>
                <p class="mb-0 small">
                    Phase 5 : encaissements, mode de reglement et mouvement de caisse.
                    Rappel : seul un reglement en especes touchera la caisse.
                </p>
            </div>
        </div>
    @endif

    <p class="mt-3"><a href="{{ route('ventes.index') }}">Retour aux ventes</a></p>
@endsection
