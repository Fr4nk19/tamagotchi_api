<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetAction extends Model
{
    protected $fillable = [
        'pet_id',
        'action',
        'stat_changes',
    ];

    protected function casts(): array
    {
        return [
            'stat_changes' => 'array',
        ];
    }

    /**
     * Get the pet that owns the action.
     */
    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }
}
