@extends('layouts.app')

@section('title', 'Modifier '.$vente->numero_facture)

@section('content')
    <h1 class="h3 mb-4">Modifier l'en-tete de {{ $vente->numero_facture }}</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('ventes.update', $vente) }}">
                @csrf
                @method('PUT')
                @include('ventes._form')
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="{{ route('ventes.show', $vente) }}" class="btn btn-link">Annuler</a>
                </div>
            </form>
        </div>
    </div>
@endsection
