@include('partials.erreurs')

<div class="row g-3">
    <div class="col-md-4">
        <label for="date_charge" class="form-label">Date de charge</label>
        <input type="date" id="date_charge" name="date_charge"
               value="{{ old('date_charge', $charge->date_charge?->toDateString()) }}"
               class="form-control @error('date_charge') is-invalid @enderror" required>
        @error('date_charge')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">La date de la depense, distincte de la date de paiement.</div>
    </div>

    <div class="col-md-4">
        <label for="lot_id" class="form-label">Lot concerne</label>
        <select id="lot_id" name="lot_id" class="form-select @error('lot_id') is-invalid @enderror" required>
            <option value="">— Choisir un lot —</option>
            @foreach ($lots as $l)
                <option value="{{ $l->id }}" {{ (int) old('lot_id', $charge->lot_id) === $l->id ? 'selected' : '' }}>
                    {{ $l->numero }} — {{ $l->modele->designation }}
                </option>
            @endforeach
        </select>
        @error('lot_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="categorie" class="form-label">Categorie</label>
        <select id="categorie" name="categorie" class="form-select @error('categorie') is-invalid @enderror" required>
            @foreach (['tissu' => 'Tissu', 'couture' => 'Couture', 'impression' => 'Impression', 'autre' => 'Autre'] as $valeur => $libelle)
                <option value="{{ $valeur }}" {{ old('categorie', $charge->categorie) === $valeur ? 'selected' : '' }}>
                    {{ $libelle }}
                </option>
            @endforeach
        </select>
        @error('categorie')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-8">
        <label for="designation" class="form-label">Designation</label>
        <input type="text" id="designation" name="designation" value="{{ old('designation', $charge->designation) }}"
               class="form-control @error('designation') is-invalid @enderror"
               placeholder="Tissu bleu 100 metres" required>
        @error('designation')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="fournisseur_id" class="form-label">Fournisseur <span class="text-body-secondary">(facultatif)</span></label>
        <select id="fournisseur_id" name="fournisseur_id" class="form-select @error('fournisseur_id') is-invalid @enderror">
            <option value="">— Aucun —</option>
            @foreach ($fournisseurs as $f)
                <option value="{{ $f->id }}" {{ (int) old('fournisseur_id', $charge->fournisseur_id) === $f->id ? 'selected' : '' }}>
                    {{ $f->nom }}
                </option>
            @endforeach
        </select>
        @error('fournisseur_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="quantite" class="form-label">Quantite</label>
        <input type="number" id="quantite" name="quantite" step="0.001" min="0.001"
               value="{{ old('quantite', $charge->quantite ?? '1.000') }}"
               class="form-control @error('quantite') is-invalid @enderror" required>
        @error('quantite')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Pour un forfait, laissez 1.</div>
    </div>

    <div class="col-md-3">
        <label for="unite" class="form-label">Unite <span class="text-body-secondary">(facultatif)</span></label>
        <input type="text" id="unite" name="unite" value="{{ old('unite', $charge->unite) }}"
               class="form-control @error('unite') is-invalid @enderror" placeholder="metre, piece, forfait">
        @error('unite')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="prix_unitaire" class="form-label">Prix unitaire (DT)</label>
        <input type="number" id="prix_unitaire" name="prix_unitaire" step="0.001" min="0"
               value="{{ old('prix_unitaire', $charge->prix_unitaire ?? '0.000') }}"
               class="form-control @error('prix_unitaire') is-invalid @enderror" required>
        @error('prix_unitaire')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Montant</label>
        <input type="text" id="apercu_montant" class="form-control-plaintext fw-semibold" readonly value="0,000 DT">
        <div class="form-text">Calcule par la base : quantite &times; prix unitaire.</div>
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">Notes <span class="text-body-secondary">(facultatif)</span></label>
        <textarea id="notes" name="notes" rows="2"
                  class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $charge->notes) }}</textarea>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const quantite = document.getElementById('quantite');
        const prix = document.getElementById('prix_unitaire');
        const apercu = document.getElementById('apercu_montant');

        function rafraichir() {
            const montant = (parseFloat(quantite.value) || 0) * (parseFloat(prix.value) || 0);
            apercu.value = montant.toLocaleString('fr-FR', {
                minimumFractionDigits: 3,
                maximumFractionDigits: 3,
            }) + ' DT';
        }

        quantite.addEventListener('input', rafraichir);
        prix.addEventListener('input', rafraichir);
        rafraichir();
    })();
</script>
@endpush
