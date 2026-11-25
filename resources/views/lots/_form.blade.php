@include('partials.erreurs')

<div class="row g-3">
    <div class="col-md-6">
        <label for="modele_id" class="form-label">Modele fabrique</label>
        <select id="modele_id" name="modele_id"
                class="form-select @error('modele_id') is-invalid @enderror" required>
            <option value="">— Choisir un modele —</option>
            @foreach ($modeles as $m)
                <option value="{{ $m->id }}"
                    {{ (int) old('modele_id', $lot->modele_id) === $m->id ? 'selected' : '' }}>
                    {{ $m->reference }} — {{ $m->designation }}
                </option>
            @endforeach
        </select>
        @error('modele_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="quantite_produite" class="form-label">Quantite produite</label>
        <input type="number" id="quantite_produite" name="quantite_produite" min="1" step="1"
               value="{{ old('quantite_produite', $lot->quantite_produite) }}"
               class="form-control @error('quantite_produite') is-invalid @enderror" required>
        @error('quantite_produite')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="etat" class="form-label">Etat</label>
        <select id="etat" name="etat" class="form-select @error('etat') is-invalid @enderror" required>
            @foreach (['en_preparation' => 'En preparation', 'termine' => 'Termine', 'annule' => 'Annule'] as $valeur => $libelle)
                <option value="{{ $valeur }}" {{ old('etat', $lot->etat) === $valeur ? 'selected' : '' }}>
                    {{ $libelle }}
                </option>
            @endforeach
        </select>
        @error('etat')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="date_production" class="form-label">Date de production</label>
        <input type="date" id="date_production" name="date_production"
               value="{{ old('date_production', $lot->date_production?->toDateString()) }}"
               class="form-control @error('date_production') is-invalid @enderror" required>
        @error('date_production')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="date_debut" class="form-label">Date de debut <span class="text-body-secondary">(facultatif)</span></label>
        <input type="date" id="date_debut" name="date_debut"
               value="{{ old('date_debut', $lot->date_debut?->toDateString()) }}"
               class="form-control @error('date_debut') is-invalid @enderror">
        @error('date_debut')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="date_fin" class="form-label">Date de fin <span class="text-body-secondary">(facultatif)</span></label>
        <input type="date" id="date_fin" name="date_fin"
               value="{{ old('date_fin', $lot->date_fin?->toDateString()) }}"
               class="form-control @error('date_fin') is-invalid @enderror">
        @error('date_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label for="observations" class="form-label">Observations <span class="text-body-secondary">(facultatif)</span></label>
        <textarea id="observations" name="observations" rows="3"
                  class="form-control @error('observations') is-invalid @enderror">{{ old('observations', $lot->observations) }}</textarea>
        @error('observations')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
