<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        $clients = Client::withCount(['ventes' => $this->ventesValidees()])
            ->withSum(['ventes as total_facture' => $this->ventesValidees()], 'total')
            ->orderBy('nom')
            ->paginate(15);

        return view('clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('clients.create', ['client' => new Client]);
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $client = Client::create($request->validated());

        return redirect()
            ->route('clients.show', $client)
            ->with('status', "Client {$client->nom} cree.");
    }

    public function show(Client $client): View
    {
        $client->load([
            'ventes' => fn ($q) => $q->withSum('reglements as encaisse', 'montant')->orderByDesc('date_vente'),
        ]);

        $validees = $client->ventes->where('statut', 'validee');

        return view('clients.show', [
            'client' => $client,
            'totalFacture' => (float) $validees->sum('total'),
            'totalEncaisse' => (float) $validees->sum('encaisse'),
        ]);
    }

    public function edit(Client $client): View
    {
        return view('clients.edit', compact('client'));
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return redirect()
            ->route('clients.show', $client)
            ->with('status', "Client {$client->nom} modifie.");
    }

    /** Deletable only while no invoice points at the client. */
    public function destroy(Client $client): RedirectResponse
    {
        if ($client->ventes()->exists()) {
            return redirect()
                ->route('clients.show', $client)
                ->withErrors(['suppression' => 'Ce client a des ventes : il ne peut pas etre supprime.']);
        }

        $nom = $client->nom;
        $client->delete();

        return redirect()
            ->route('clients.index')
            ->with('status', "Client {$nom} supprime.");
    }

    private function ventesValidees(): callable
    {
        return fn (Builder $q) => $q->where('statut', 'validee');
    }
}
