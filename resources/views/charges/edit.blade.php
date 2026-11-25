@extends('layouts.app')

@section('title', 'Modifier '.$charge->numero_piece)

@section('content')
    <h1 class="h3 mb-4">Modifier la charge {{ $charge->numero_piece }}</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('charges.update', $charge) }}">
                @csrf
                @method('PUT')
                @include('charges._form')
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="{{ route('lots.show', $charge->lot_id) }}" class="btn btn-link">Annuler</a>
                </div>
            </form>
        </div>
    </div>

    @if ($charge->paiements()->doesntExist())
        <form method="POST" action="{{ route('charges.destroy', $charge) }}" class="mt-3"
              onsubmit="return confirm('Supprimer cette charge ?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">Supprimer cette charge</button>
        </form>
    @else
        <p class="text-body-secondary small mt-3">
            Cette charge porte des paiements : elle se corrige, elle ne se supprime pas.
        </p>
    @endif
@endsection
