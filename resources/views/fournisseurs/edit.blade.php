@extends('layouts.app')

@section('title', 'Modifier '.$fournisseur->nom)

@section('content')
    <h1 class="h3 mb-4">Modifier {{ $fournisseur->nom }}</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('fournisseurs.update', $fournisseur) }}">
                @csrf
                @method('PUT')
                @include('fournisseurs._form')
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="{{ route('fournisseurs.show', $fournisseur) }}" class="btn btn-link">Annuler</a>
                </div>
            </form>
        </div>
    </div>
@endsection
