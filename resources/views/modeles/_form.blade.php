@include('partials.erreurs')

<div class="row g-3">
    <div class="col-md-4">
        <label for="reference" class="form-label">Reference</label>
        <input type="text" id="reference" name="reference"
               value="{{ old('reference', $modele->reference) }}"
               class="form-control @error('reference') is-invalid @enderror"
               placeholder="CV001" required>
        @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-8">
        <label for="designation" class="form-label">Designation</label>
        <input type="text" id="designation" name="designation"
               value="{{ old('designation', $modele->designation) }}"
               class="form-control @error('designation') is-invalid @enderror"
               placeholder="Cache-valise moyen" required>
        @error('designation')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="dimensions" class="form-label">Dimensions <span class="text-body-secondary">(facultatif)</span></label>
        <input type="text" id="dimensions" name="dimensions"
               value="{{ old('dimensions', $modele->dimensions) }}"
               class="form-control @error('dimensions') is-invalid @enderror">
        @error('dimensions')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="prix_conseille" class="form-label">Prix conseille (DT)</label>
        <input type="number" id="prix_conseille" name="prix_conseille" step="0.001" min="0"
               value="{{ old('prix_conseille', $modele->prix_conseille ?? '0.000') }}"
               class="form-control @error('prix_conseille') is-invalid @enderror" required>
        @error('prix_conseille')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check">
            <input type="checkbox" id="actif" name="actif" value="1"
                   class="form-check-input"
                   {{ old('actif', $modele->actif ?? true) ? 'checked' : '' }}>
            <label for="actif" class="form-check-label">Modele actif</label>
        </div>
    </div>

    <div class="col-12">
        <label for="description" class="form-label">Description <span class="text-body-secondary">(facultatif)</span></label>
        <textarea id="description" name="description" rows="3"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $modele->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
