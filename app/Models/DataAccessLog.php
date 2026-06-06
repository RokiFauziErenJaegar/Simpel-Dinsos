<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataAccessLog extends Model
{
    protected $fillable = [
        'actor_user_id', 'actor_role', 'action',
        'subject_type', 'subject_id', 'subject_owner_nik',
        'reason', 'ip', 'user_agent', 'route',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * Catat akses data sensitif. Dipanggil dari controller/observer.
     */
    public static function record(string $action, $subject = null, ?string $ownerNik = null, ?string $reason = null): self
    {
        $request = request();
        $user = $request?->user();

        $subjectType = null;
        $subjectId = null;
        if (is_object($subject)) {
            $subjectType = class_basename($subject);
            $subjectId = $subject->getKey();
        } elseif (is_string($subject)) {
            $subjectType = $subject;
        }

        return static::create([
            'actor_user_id' => $user?->id,
            'actor_role' => $user?->role?->value,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'subject_owner_nik' => $ownerNik,
            'reason' => $reason,
            'ip' => $request?->ip(),
            'user_agent' => substr((string) $request?->userAgent(), 0, 500),
            'route' => $request?->route()?->getName() ?? $request?->path(),
        ]);
    }
}
