{{-- Shared by a client reglement and a supplier payment. --}}
<div class="row g-3 align-items-end">
    <div class="col-md-3">
        <label for="{{ $champDate }}" class="form-label">Date</label>
        <input type="date" id="{{ $champDate }}" name="{{ $champDate }}"
               value="{{ old($champDate, now()->toDateString()) }}" class="form-control" required>
    </div>
    <div class="col-md-3">
        <label for="montant" class="form-label">Montant (DT)</label>
        <input type="number" id="montant" name="montant" step="0.001" min="0.001"
               max="{{ $maximum }}" value="{{ old('montant', number_format($maximum, 3, '.', '')) }}"
               class="form-control" required>
        <div class="form-text">Reste : {{ number_format($maximum, 3, ',', ' ') }} DT</div>
    </div>
    <div class="col-md-2">
        <label for="mode" class="form-label">Mode</label>
        <select id="mode" name="mode" class="form-select" required>
            <option value="espece" {{ old('mode') === 'espece' ? 'selected' : '' }}>Especes</option>
            <option value="virement" {{ old('mode') === 'virement' ? 'selected' : '' }}>Virement</option>
            <option value="cheque" {{ old('mode') === 'cheque' ? 'selected' : '' }}>Cheque</option>
        </select>
    </div>
    <div class="col-md-2">
        <label for="reference" class="form-label">Reference</label>
        <input type="text" id="reference" name="reference" value="{{ old('reference') }}"
               class="form-control" placeholder="N° cheque">
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">Enregistrer</button>
    </div>
</div>
<p class="form-text mt-2 mb-0">
    Seul un paiement en <strong>especes</strong> cree un mouvement de caisse.
    Un virement ou un cheque est enregistre sans toucher le solde especes.
</p>
