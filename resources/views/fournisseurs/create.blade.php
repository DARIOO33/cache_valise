@extends('layouts.app')

@section('title', 'Ajouter un fournisseur')

@section('content')
    <h1 class="h3 mb-4">Ajouter un fournisseur</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('fournisseurs.store') }}">
                @csrf
                @include('fournisseurs._form')
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="{{ route('fournisseurs.index') }}" class="btn btn-link">Annuler</a>
                </div>
            </form>
        </div>
    </div>
@endsection
