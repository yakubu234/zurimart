<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuditTrailService
{
    public function record(string $action, Model $model): void
    {
        if ($model instanceof AuditLog || ! Schema::hasTable('audit_logs')) {
            return;
        }

        [$oldValues, $newValues] = $this->changesFor($action, $model);

        if ($action === 'updated' && empty($oldValues) && empty($newValues)) {
            return;
        }

        $modelName = Str::headline(class_basename($model));
        $subjectLabel = $this->subjectLabel($model);

        $this->store($action, $model, $oldValues, $newValues, ucfirst($action) . " {$modelName}" . ($subjectLabel ? ": {$subjectLabel}" : ''));
    }

    public function recordChange(Model $model, string $description, array $oldValues, array $newValues): void
    {
        if ($model instanceof AuditLog || ! Schema::hasTable('audit_logs') || $oldValues === $newValues) {
            return;
        }

        $this->store(
            'updated',
            $model,
            $this->sanitize($model, $oldValues),
            $this->sanitize($model, $newValues),
            $description
        );
    }

    public function recordActivity(string $action, Model $model, string $description): void
    {
        if ($model instanceof AuditLog || ! Schema::hasTable('audit_logs')) {
            return;
        }

        $this->store($action, $model, [], [], $description, $model instanceof User ? $model->id : null);
    }

    private function store(
        string $action,
        Model $model,
        array $oldValues,
        array $newValues,
        string $description,
        ?int $actorId = null
    ): void
    {
        $request = app()->bound('request') ? request() : null;

        AuditLog::query()->create([
            'user_id' => $actorId ?? Auth::id(),
            'branch_id' => $this->branchId($model),
            'action' => $action,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey() === null ? null : (string) $model->getKey(),
            'subject_label' => $this->subjectLabel($model),
            'description' => $description,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => $request?->ip(),
            'method' => $request?->method(),
            'url' => $request?->fullUrl(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 2000, ''),
        ]);
    }

    private function changesFor(string $action, Model $model): array
    {
        if ($action === 'created') {
            return [[], $this->sanitize($model, $this->withoutNoise($model->getAttributes()))];
        }

        if ($action === 'deleted') {
            return [$this->sanitize($model, $this->withoutNoise($model->getAttributes())), []];
        }

        $changes = $this->withoutNoise($model->getChanges());
        $oldValues = [];

        foreach (array_keys($changes) as $key) {
            $oldValues[$key] = $model->getRawOriginal($key);
        }

        return [
            $this->sanitize($model, $oldValues),
            $this->sanitize($model, $changes),
        ];
    }

    private function withoutNoise(array $values): array
    {
        unset($values['created_at'], $values['updated_at'], $values['remember_token']);

        return $values;
    }

    private function sanitize(Model $model, array $values): array
    {
        foreach ($values as $key => $value) {
            if (preg_match('/password|token|secret|credential|api[_-]?key/i', (string) $key)) {
                $values[$key] = '[REDACTED]';
            }
        }

        if ($model instanceof AppSetting
            && preg_match('/password|token|secret|credential|api[_-]?key/i', (string) $model->key)
            && array_key_exists('value', $values)) {
            $values['value'] = '[REDACTED]';
        }

        return $values;
    }

    private function subjectLabel(Model $model): ?string
    {
        foreach (['order_number', 'name', 'title', 'code', 'sku', 'email', 'key'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (filled($value)) {
                return Str::limit((string) $value, 255, '');
            }
        }

        return $model->getKey() === null ? null : '#' . $model->getKey();
    }

    private function branchId(Model $model): ?int
    {
        if ($model instanceof Branch) {
            return (int) $model->getKey();
        }

        $branchId = $model->getAttribute('branch_id');

        return filled($branchId) ? (int) $branchId : null;
    }
}
