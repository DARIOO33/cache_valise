<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Section 4: "les operations sensibles doivent etre historisees".
 *
 * Writes an append-only trail of who changed what, and when. Nothing here
 * ever updates or deletes a previous entry.
 */
class AuditService
{
    public function enregistrer(
        string $action,
        Model $auditable,
        ?array $anciennes = null,
        ?array $nouvelles = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'anciennes_valeurs' => $this->nettoyer($anciennes),
            'nouvelles_valeurs' => $this->nettoyer($nouvelles),
            'ip' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    /** Timestamps carry no business meaning; keep the trail readable. */
    private function nettoyer(?array $valeurs): ?array
    {
        if ($valeurs === null) {
            return null;
        }

        return collect($valeurs)
            ->except(['created_at', 'updated_at'])
            ->all() ?: null;
    }
}
