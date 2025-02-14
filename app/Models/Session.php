<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Session extends Model
{
    use HasFactory;

    protected $table = 'sessions'; // Kopplar modellen till sessions-tabellen

    protected $primaryKey = 'id'; // Viktigt eftersom session_id är en string (UUID)

    public $incrementing = false; // Laravel antar att id är en integer, detta fixar det

    protected $keyType = 'string'; // Eftersom session_id är en UUID (string)

    protected $fillable = [
        'id',
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
    ];

    /**
     * Relation till User (om användaren finns)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation till ChatHistory
     */
    public function chatHistories()
    {
        return $this->hasMany(ChatHistory::class, 'session_id', 'id');
    }
}