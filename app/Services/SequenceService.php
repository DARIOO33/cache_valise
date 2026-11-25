<?php

namespace App\Services;

use App\Models\Parametre;
use App\Models\Sequence;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Allocates the unique, per-year document numbers required by section 5:
 * LOT-2026-001, FAC-2026-0001, PC-2026-0001.
 *
 * The counter is read and written under a row lock inside a transaction, so
 * two submissions landing at the same instant cannot take the same number.
 */
class SequenceService
{
    private const FORMATS = [
        'lot' => ['parametre' => 'prefixe_lot', 'defaut' => 'LOT', 'padding' => 3],
        'facture' => ['parametre' => 'prefixe_facture', 'defaut' => 'FAC', 'padding' => 4],
        'piece' => ['parametre' => 'prefixe_piece', 'defaut' => 'PC', 'padding' => 4],
    ];

    public function suivant(string $cle, ?int $annee = null): string
    {
        if (! array_key_exists($cle, self::FORMATS)) {
            throw new InvalidArgumentException("Sequence inconnue : {$cle}");
        }

        $format = self::FORMATS[$cle];
        $annee ??= (int) now()->format('Y');

        $numero = DB::transaction(function () use ($cle, $annee) {
            Sequence::firstOrCreate(
                ['cle' => $cle, 'annee' => $annee],
                ['dernier_numero' => 0]
            );

            $sequence = Sequence::where('cle', $cle)
                ->where('annee', $annee)
                ->lockForUpdate()
                ->first();

            $sequence->dernier_numero++;
            $sequence->save();

            return $sequence->dernier_numero;
        });

        $prefixe = Parametre::where('cle', $format['parametre'])->value('valeur') ?: $format['defaut'];

        return sprintf('%s-%d-%0'.$format['padding'].'d', $prefixe, $annee, $numero);
    }
}
