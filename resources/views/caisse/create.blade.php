@extends('layouts.app')

@section('title', 'Nouveau mouvement de caisse')

@section('content')
    <h1 class="h3 mb-4">Nouveau mouvement de caisse</h1>

    @include('partials.erreurs')

    <div class="card shadow-sm">
        <div class="card-body">
            <p class="text-body-secondary small">
                Les reglements clients et les paiements de charges ne se saisissent pas ici :
                ils sont crees automatiquement depuis la facture ou la charge concernee.
            </p>

            <form method="POST" action="{{ route('caisse.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="date_mouvement" class="form-label">Date</label>
                        <input type="date" id="date_mouvement" name="date_mouvement"
                               value="{{ old('date_mouvement', now()->toDateString()) }}"
                               class="form-control @error('date_mouvement') is-invalid @enderror" required>
                        @error('date_mouvement')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label for="type" class="form-label">Type</label>
                        <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                            @unless ($soldeInitialExiste)
                                <option value="solde_initial" {{ old('type') === 'solde_initial' ? 'selected' : '' }}>Solde initial (entree)</option>
                            @endunless
                            <option value="alimentation" {{ old('type') === 'alimentation' ? 'selected' : '' }}>Alimentation (entree)</option>
                            <option value="retrait" {{ old('type') === 'retrait' ? 'selected' : '' }}>Retrait (sortie)</option>
                            <option value="depot_bancaire" {{ old('type') === 'depot_bancaire' ? 'selected' : '' }}>Depot bancaire (sortie)</option>
                            <option value="correction" {{ old('type') === 'correction' ? 'selected' : '' }}>Correction</option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4" id="bloc_sens">
                        <label for="sens" class="form-label">Sens de la correction</label>
                        <select id="sens" name="sens" class="form-select @error('sens') is-invalid @enderror">
                            <option value="entree" {{ old('sens') === 'entree' ? 'selected' : '' }}>Entree</option>
                            <option value="sortie" {{ old('sens') === 'sortie' ? 'selected' : '' }}>Sortie</option>
                        </select>
                        @error('sens')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label for="montant" class="form-label">Montant (DT)</label>
                        <input type="number" id="montant" name="montant" step="0.001" min="0.001"
                               value="{{ old('montant') }}"
                               class="form-control @error('montant') is-invalid @enderror" required>
                        @error('montant')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-5">
                        <label for="libelle" class="form-label">Libelle</label>
                        <input type="text" id="libelle" name="libelle" value="{{ old('libelle') }}"
                               class="form-control @error('libelle') is-invalid @enderror" required>
                        @error('libelle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-3">
                        <label for="reference" class="form-label">Reference <span class="text-body-secondary">(facultatif)</span></label>
                        <input type="text" id="reference" name="reference" value="{{ old('reference') }}"
                               class="form-control" placeholder="Banque, n° bordereau">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="{{ route('caisse.index') }}" class="btn btn-link">Annuler</a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        const typeSelect = document.getElementById('type');
        const blocSens = document.getElementById('bloc_sens');

        function majSens() {
            blocSens.style.display = typeSelect.value === 'correction' ? '' : 'none';
        }

        typeSelect.addEventListener('change', majSens);
        majSens();
    </script>
    @endpush
@endsection
