<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $event_id
 * @property string $name
 * @property string $email
 * @property string $status
 * @property Carbon|null $reminded_3_days_at
 * @property Carbon|null $reminded_24_hours_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Attendee extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'reminded_3_days_at' => 'datetime',
        'reminded_24_hours_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
