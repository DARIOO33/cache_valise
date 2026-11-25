<?php

namespace App\Http\Controllers;

use App\Services\CashService;
use App\Services\ReportService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(ReportService $rapports, CashService $caisse): View
    {
        $debut = now()->startOfMonth()->toDateString();
        $fin = now()->endOfMonth()->toDateString();

        return view('dashboard', [
            'caMois' => $rapports->chiffreAffaires($debut, $fin),
            'margeMois' => $rapports->margeBrute($debut, $fin),
            'chargesMois' => $rapports->totalCharges($debut, $fin),
            'caTotal' => $rapports->chiffreAffaires(),
            'margeTotale' => $rapports->margeBrute(),
            'solde' => $caisse->solde(),
            'creances' => $rapports->totalCreances(),
            'dettes' => $rapports->totalDettes(),
            'ventesParMois' => $rapports->ventesParMois(),
            'chargesParCategorie' => $rapports->chargesParCategorie(),
        ]);
    }
}
