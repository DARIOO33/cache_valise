@include('partials.erreurs')

<div class="row g-3">
    <div class="col-md-4">
        <label for="date_vente" class="form-label">Date de la facture</label>
        <input type="date" id="date_vente" name="date_vente"
               value="{{ old('date_vente', $vente->date_vente?->toDateString()) }}"
               class="form-control @error('date_vente') is-invalid @enderror" required>
        @error('date_vente')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="client_id" class="form-label">Client</label>
        <select id="client_id" name="client_id" class="form-select @error('client_id') is-invalid @enderror">
            <option value="">— Vente comptoir (sans client) —</option>
            @foreach ($clients as $c)
                <option value="{{ $c->id }}" {{ (int) old('client_id', $vente->client_id) === $c->id ? 'selected' : '' }}>
                    {{ $c->nom }}
                </option>
            @endforeach
        </select>
        @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="nom_comptoir" class="form-label">Nom comptoir <span class="text-body-secondary">(facultatif)</span></label>
        <input type="text" id="nom_comptoir" name="nom_comptoir" value="{{ old('nom_comptoir', $vente->nom_comptoir) }}"
               class="form-control @error('nom_comptoir') is-invalid @enderror"
               placeholder="Pour une vente sans client enregistre">
        @error('nom_comptoir')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">Notes <span class="text-body-secondary">(facultatif)</span></label>
        <textarea id="notes" name="notes" rows="2"
                  class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $vente->notes) }}</textarea>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
