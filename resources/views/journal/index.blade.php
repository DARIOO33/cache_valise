@extends('layouts.app')

@section('title', 'Journal des operations')

@php
    $libellesAction = [
        'creation' => 'Creation', 'modification' => 'Modification', 'suppression' => 'Suppression',
        'validation_facture' => 'Validation de facture', 'annulation_facture' => 'Annulation de facture',
        'contre_ecriture' => 'Contre-ecriture',
    ];
    $couleursAction = [
        'creation' => 'success', 'modification' => 'secondary', 'suppression' => 'danger',
        'validation_facture' => 'primary', 'annulation_facture' => 'danger', 'contre_ecriture' => 'warning',
    ];
@endphp

@section('content')
    <h1 class="h3 mb-1">Journal des operations</h1>
    <p class="text-body-secondary mb-4">
        Historique des operations sensibles (section 4). Ce journal est en lecture seule :
        aucune entree ne peut etre modifiee ni supprimee.
    </p>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('journal.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="f_action" class="form-label small mb-1">Action</label>
                    <select id="f_action" name="action" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" {{ ($filtres['action'] ?? '') === $action ? 'selected' : '' }}>
                                {{ $libellesAction[$action] ?? $action }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="f_type" class="form-label small mb-1">Enregistrement</label>
                    <select id="f_type" name="type" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach ($types as $type)
                            <option value="{{ $type }}" {{ ($filtres['type'] ?? '') === $type ? 'selected' : '' }}>
                                {{ class_basename($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="f_du" class="form-label small mb-1">Du</label>
                    <input type="date" id="f_du" name="du" value="{{ $filtres['du'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label for="f_au" class="form-label small mb-1">Au</label>
                    <input type="date" id="f_au" name="au" value="{{ $filtres['au'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-outline-primary">Filtrer</button>
                    <a href="{{ route('journal.index') }}" class="btn btn-sm btn-link">Tout</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th><th>Utilisateur</th><th>Action</th>
                        <th>Enregistrement</th><th>Details</th><th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entrees as $entree)
                        <tr>
                            <td class="text-nowrap">{{ $entree->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $entree->user?->name ?? 'systeme' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $couleursAction[$entree->action] ?? 'secondary' }}">
                                    {{ $libellesAction[$entree->action] ?? $entree->action }}
                                </span>
                            </td>
                            <td class="small">
                                {{ class_basename($entree->auditable_type) }} #{{ $entree->auditable_id }}
                            </td>
                            <td class="small text-body-secondary" style="max-width: 32rem;">
                                @php
                                    $details = collect($entree->nouvelles_valeurs ?? [])
                                        ->except(['id'])
                                        ->map(fn ($v, $k) => $k.': '.(is_scalar($v) ? $v : json_encode($v)))
                                        ->take(5)
                                        ->join(' · ');
                                @endphp
                                {{ $details ?: '—' }}
                            </td>
                            <td class="small text-body-secondary">{{ $entree->ip }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">Aucune operation enregistree.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $entrees->links() }}</div>
@endsection
