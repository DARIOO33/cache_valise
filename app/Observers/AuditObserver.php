<?php

namespace App\Observers;

use App\Models\Vente;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

/**
 * Records every create, change and delete on the records that carry money.
 *
 * Business events with a name of their own - validating or cancelling an
 * invoice, writing a contre-ecriture - are logged by the services instead,
 * so the trail reads in business terms rather than column diffs.
 */
class AuditObserver
{
    /**
     * Columns whose change alone is not worth an entry: they are recomputed
     * by the application, not decided by the user.
     */
    private const RECALCULES = [
        Vente::class => ['sous_total', 'total_remise', 'total'],
    ];

    public function __construct(private readonly AuditService $audit) {}

    public function created(Model $model): void
    {
        $this->audit->enregistrer('creation', $model, null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changements = collect($model->getChanges())
            ->except(array_merge(['updated_at'], self::RECALCULES[$model::class] ?? []));

        if ($changements->isEmpty()) {
            return;
        }

        $this->audit->enregistrer(
            'modification',
            $model,
            collect($model->getOriginal())->only($changements->keys())->all(),
            $changements->all(),
        );
    }

    public function deleted(Model $model): void
    {
        $this->audit->enregistrer('suppression', $model, $model->getOriginal(), null);
    }
}
