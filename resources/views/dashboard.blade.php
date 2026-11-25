@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
    <h1 class="h3 mb-4">Tableau de bord</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <p class="mb-1">Socle installe et operationnel.</p>
            <p class="text-body-secondary small mb-0">
                Laravel {{ Illuminate\Foundation\Application::VERSION }} &middot;
                PHP {{ PHP_VERSION }} &middot;
                Base : {{ config('database.connections.'.config('database.default').'.database') }}
            </p>
        </div>
    </div>

    {{-- Phase 6 : KPI, graphiques Chart.js, ventes, charges et solde de caisse. --}}
@endsection
