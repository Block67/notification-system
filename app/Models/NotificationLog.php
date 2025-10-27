<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class NotificationLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'notifications_log';

    protected $fillable = [
        'api_key_id',
        'type',
        'recipient',
        'status',
        'payload',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Relation : chaque log appartient à une clé API.
     */
    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class, 'api_key_id');
    }

    /**
     * Marquer la notification comme envoyée.
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Marquer la notification comme échouée.
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }
}

class PushSubscription extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
}
