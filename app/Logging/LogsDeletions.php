<?php

namespace App\Logging;

use Illuminate\Database\Eloquent\Model;

trait LogsDeletions
{
    public static function bootLogsDeletions(): void
    {
        static::deleting(function (Model $model): void {
            // Soft delete or force delete distinguished later
            $model->logDeletion('deleted');
        });

        static::forceDeleted(function (Model $model): void {
            $model->logDeletion('force_deleted');
        });
    }

    protected function logDeletion(string $action): void
    {
        $user = request()?->user();
        $snapshot = $this->deletionSnapshot();
        activity('data_ops')
            ->event($action)
            ->performedOn($this)
            ->when($user, fn ($log) => $log->causedBy($user))
            ->withProperties([
                'snapshot' => $snapshot,
                'model' => class_basename($this),
                'action' => $action,
            ])
            ->log('model.'.$action);
    }

    protected function deletionSnapshot(): array
    {
        $fields = array_intersect(array_keys($this->getAttributes()), ['id','name','email','title']);
        return collect($fields)->mapWithKeys(fn ($f) => [$f => $this->getAttribute($f)])->all();
    }
}
