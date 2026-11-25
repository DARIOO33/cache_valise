<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 mb-0">{{ $titre }}</h2>
            @unless ($vide)
                <a href="{{ $csv }}" class="btn btn-sm btn-outline-secondary">CSV</a>
            @endunless
        </div>

        @if ($vide)
            <p class="text-body-secondary mb-0">Aucune donnee sur cette periode.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            @foreach ($entetes as $i => $entete)
                                <th class="{{ $i >= 2 ? 'text-end' : '' }}">{{ $entete }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lignes as $ligne)
                            <tr>
                                @foreach ($ligne as $i => $cellule)
                                    <td class="{{ $i >= 2 ? 'text-end' : '' }}">
                                        @if (is_array($cellule))
                                            <x-montant :value="$cellule['montant']"/>
                                        @else
                                            {{ $cellule }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
