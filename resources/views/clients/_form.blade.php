@include('partials.erreurs')

<div class="row g-3">
    <div class="col-md-6">
        <label for="nom" class="form-label">Nom</label>
        <input type="text" id="nom" name="nom" value="{{ old('nom', $client->nom) }}"
               class="form-control @error('nom') is-invalid @enderror" required autofocus>
        @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="telephone" class="form-label">Telephone</label>
        <input type="text" id="telephone" name="telephone" value="{{ old('telephone', $client->telephone) }}"
               class="form-control @error('telephone') is-invalid @enderror">
        @error('telephone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="email" class="form-label">E-mail <span class="text-body-secondary">(facultatif)</span></label>
        <input type="email" id="email" name="email" value="{{ old('email', $client->email) }}"
               class="form-control @error('email') is-invalid @enderror">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label for="adresse" class="form-label">Adresse <span class="text-body-secondary">(facultatif)</span></label>
        <input type="text" id="adresse" name="adresse" value="{{ old('adresse', $client->adresse) }}"
               class="form-control @error('adresse') is-invalid @enderror">
        @error('adresse')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">Notes <span class="text-body-secondary">(facultatif)</span></label>
        <textarea id="notes" name="notes" rows="2"
                  class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $client->notes) }}</textarea>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
