<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Arr;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->writeAuditLog('created', null, $model->getAuditableAttributes());
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            unset($changes['updated_at']);

            if ($changes === []) {
                return;
            }

            $old = Arr::only($model->getOriginal(), array_keys($changes));
            $model->writeAuditLog('updated', $old, $changes);
        });

        static::deleted(function ($model) {
            $model->writeAuditLog('deleted', $model->getAuditableAttributes(), null);
        });
    }

    protected function getAuditableAttributes(): array
    {
        $hidden = array_merge($this->getHidden(), ['password', 'remember_token']);

        return Arr::except($this->attributesToArray(), $hidden);
    }

    protected function writeAuditLog(string $event, ?array $old, ?array $new): void
    {
        AuditLog::create([
            'organization_id' => $this->organization_id ?? auth()->user()?->organization_id,
            'user_id' => auth()->id(),
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'event' => $event,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
