@extends('layouts.app')

@section('title', 'Modifier '.$modele->reference)

@section('content')
    <h1 class="h3 mb-4">Modifier le modele {{ $modele->reference }}</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('modeles.update', $modele) }}">
                @csrf
                @method('PUT')
                @include('modeles._form')

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="{{ route('modeles.index') }}" class="btn btn-link">Annuler</a>
                </div>
            </form>
        </div>
    </div>
@endsection
