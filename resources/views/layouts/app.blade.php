<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @stack('styles')
</head>
<body class="bg-body-tertiary">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="{{ route('dashboard') }}">{{ config('app.name') }}</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                           href="{{ route('dashboard') }}">Tableau de bord</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('modeles.*') ? 'active' : '' }}"
                           href="{{ route('modeles.index') }}">Modeles</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('lots.*') ? 'active' : '' }}"
                           href="{{ route('lots.index') }}">Lots</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('charges.*') ? 'active' : '' }}"
                           href="{{ route('charges.index') }}">Charges</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('fournisseurs.*') ? 'active' : '' }}"
                           href="{{ route('fournisseurs.index') }}">Fournisseurs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('ventes.*') ? 'active' : '' }}"
                           href="{{ route('ventes.index') }}">Ventes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}"
                           href="{{ route('clients.index') }}">Clients</a>
                    </li>
                    {{-- Next phases: reglements, caisse, rapports. --}}
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            {{ Auth::user()?->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item" type="submit">Deconnexion</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container-fluid px-4 py-4">
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
