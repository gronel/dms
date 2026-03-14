<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    /**
     * Record an audit event.
     *
     * @param  Model       $subject   The model being acted on
     * @param  string      $action    Short action key, e.g. "upload", "update", "delete"
     * @param  array|null  $oldValues Attribute snapshot before the action
     * @param  array|null  $newValues Attribute snapshot after the action
     * @param  string|null $description Optional human-readable description
     */
    public function log(
        Model $subject,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
    ): AuditLog {
        /** @var Request $request */
        $request = app(Request::class);

        return AuditLog::create([
            'user_id'        => $request->user()?->id,
            'auditable_type' => $subject->getMorphClass(),
            'auditable_id'   => $subject->getKey(),
            'action'         => $action,
            'description'    => $description,
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
            'ip_address'     => $request->ip(),
            'user_agent'     => mb_substr((string) $request->userAgent(), 0, 500),
        ]);
    }

    /**
     * Convenience: log attribute changes between an original and updated model.
     * Only records the keys that actually changed.
     */
    public function logChange(
        Model $subject,
        string $action,
        array $originalAttributes,
        array $newAttributes,
        ?string $description = null,
    ): AuditLog {
        $changedKeys = array_keys(array_diff_assoc($newAttributes, $originalAttributes));

        $old = array_intersect_key($originalAttributes, array_flip($changedKeys));
        $new = array_intersect_key($newAttributes, array_flip($changedKeys));

        return $this->log($subject, $action, $old ?: null, $new ?: null, $description);
    }
}
