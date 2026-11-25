@extends('layouts.app')

@section('title', 'Ajouter une charge')

@section('content')
    <h1 class="h3 mb-4">Ajouter une charge</h1>

    @if ($lots->isEmpty())
        <div class="alert alert-warning">
            Aucun lot disponible. <a href="{{ route('lots.create') }}">Creez d'abord un lot</a>.
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <p class="text-body-secondary small">Le numero de piece est attribue automatiquement.</p>
            <form method="POST" action="{{ route('charges.store') }}">
                @csrf
                @include('charges._form')
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" @disabled($lots->isEmpty())>Enregistrer</button>
                    <a href="{{ route('charges.index') }}" class="btn btn-link">Annuler</a>
                </div>
            </form>
        </div>
    </div>
@endsection
