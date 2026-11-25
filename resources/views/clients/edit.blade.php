@extends('layouts.app')

@section('title', 'Modifier '.$client->nom)

@section('content')
    <h1 class="h3 mb-4">Modifier {{ $client->nom }}</h1>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('clients.update', $client) }}">
                @csrf
                @method('PUT')
                @include('clients._form')
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="{{ route('clients.show', $client) }}" class="btn btn-link">Annuler</a>
                </div>
            </form>
        </div>
    </div>
@endsection
