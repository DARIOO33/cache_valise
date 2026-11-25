@extends('layouts.app')

@section('title', 'Creer un lot')

@section('content')
    <h1 class="h3 mb-4">Creer un lot de production</h1>

    @if ($modeles->isEmpty())
        <div class="alert alert-warning">
            Aucun modele actif. <a href="{{ route('modeles.create') }}">Creez d'abord un modele</a>.
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <p class="text-body-secondary small">
                Le numero de lot est attribue automatiquement a l'enregistrement.
            </p>

            <form method="POST" action="{{ route('lots.store') }}">
                @csrf
                @include('lots._form')

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" @disabled($modeles->isEmpty())>Enregistrer</button>
                    <a href="{{ route('lots.index') }}" class="btn btn-link">Annuler</a>
                </div>
            </form>
        </div>
    </div>
@endsection
