@extends('layouts.app')

@section('title', 'Creer une facture')

@section('content')
    <h1 class="h3 mb-4">Creer une facture</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <p class="text-body-secondary small">
                Le numero de facture est attribue immediatement. Les lignes s'ajoutent a l'etape suivante,
                puis la facture est validee.
            </p>
            <form method="POST" action="{{ route('ventes.store') }}">
                @csrf
                @include('ventes._form')
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Creer le brouillon</button>
                    <a href="{{ route('ventes.index') }}" class="btn btn-link">Annuler</a>
                </div>
            </form>
        </div>
    </div>
@endsection
