<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ApiKey extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'key',
        'secret',
        'is_active',
        'permissions',
        'rate_limit',
        'last_used_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Vérifie que le secret fourni correspond au hash stocké.
     */
    public function verifySecret(string $secret): bool
    {
        return hash_equals($this->secret, hash('sha256', $secret));
    }

    /**
     * Vérifie si la clé API possède une permission donnée.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Met à jour la date de dernière utilisation.
     */
    public function updateLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Relation : une clé API peut avoir plusieurs logs de notification.
     */
    public function notificationLogs()
    {
        return $this->hasMany(NotificationLog::class, 'api_key_id');
    }

    /**
     * Génère automatiquement une clé et un secret s’ils ne sont pas fournis.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->key)) {
                $model->key = bin2hex(random_bytes(16)); // 32 caractères hex
            }

            if (empty($model->secret)) {
                $model->secret = hash('sha256', bin2hex(random_bytes(32))); // Secret hashé
            }
        });
    }
}
