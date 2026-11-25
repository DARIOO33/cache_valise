@extends('layouts.app')

@section('title', 'Modifier '.$lot->numero)

@section('content')
    <h1 class="h3 mb-4">Modifier le lot {{ $lot->numero }}</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('lots.update', $lot) }}">
                @csrf
                @method('PUT')
                @include('lots._form')

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="{{ route('lots.show', $lot) }}" class="btn btn-link">Annuler</a>
                </div>
            </form>
        </div>
    </div>
@endsection
