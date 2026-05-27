<?php

namespace App\Models;

use App\Enums\PetStage;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Pet extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'stage',
        'hunger',
        'happiness',
        'energy',
        'cleanliness',
        'health',
        'weight',
        'age_minutes',
        'is_alive',
        'is_sleeping',
        'born_at',
        'last_interaction_at',
        'last_calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'stage' => PetStage::class,
            'hunger' => 'integer',
            'happiness' => 'integer',
            'energy' => 'integer',
            'cleanliness' => 'integer',
            'health' => 'integer',
            'weight' => 'integer',
            'age_minutes' => 'integer',
            'is_alive' => 'boolean',
            'is_sleeping' => 'boolean',
            'born_at' => 'datetime',
            'last_interaction_at' => 'datetime',
            'last_calculated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the pet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the actions for the pet.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(PetAction::class);
    }

    /**
     * Calculate current stats based on elapsed time since last calculation.
     * This is the core mechanic - stats decay over time even when the user is away.
     */
    public function calculateCurrentStats(): self
    {
        if (!$this->is_alive) {
            return $this;
        }

        $now = Carbon::now();
        $lastCalculated = $this->last_calculated_at ?? $this->born_at ?? $now;
        $minutesElapsed = (int) $lastCalculated->diffInMinutes($now);

        if ($minutesElapsed <= 0) {
            return $this;
        }

        // Decrease hunger: -1 per 3 minutes
        $hungerLoss = (int) floor($minutesElapsed / 3);
        $this->hunger = max(0, $this->hunger - $hungerLoss);

        // Decrease happiness: -1 per 5 minutes
        $happinessLoss = (int) floor($minutesElapsed / 5);
        $this->happiness = max(0, $this->happiness - $happinessLoss);

        // Energy: -1 per 4 minutes (unless sleeping, then +1 per 2 minutes)
        if ($this->is_sleeping) {
            $energyGain = (int) floor($minutesElapsed / 2);
            $this->energy = min(100, $this->energy + $energyGain);
        } else {
            $energyLoss = (int) floor($minutesElapsed / 4);
            $this->energy = max(0, $this->energy - $energyLoss);
        }

        // Decrease cleanliness: -1 per 6 minutes
        $cleanlinessLoss = (int) floor($minutesElapsed / 6);
        $this->cleanliness = max(0, $this->cleanliness - $cleanlinessLoss);

        // Health decreases if hunger < 10 or cleanliness < 10
        if ($this->hunger < 10 || $this->cleanliness < 10) {
            $healthLoss = (int) floor($minutesElapsed / 5);
            $this->health = max(0, $this->health - $healthLoss);
        }

        // If health reaches 0, pet dies
        if ($this->health <= 0) {
            $this->health = 0;
            $this->is_alive = false;
        }

        // Update age
        $totalAge = (int) $this->born_at->diffInMinutes($now);
        $this->age_minutes = $totalAge;

        // Check for evolution
        if ($this->is_alive) {
            $this->stage = PetStage::fromAge($totalAge);
        }

        // Update last_calculated_at
        $this->last_calculated_at = $now;

        $this->save();

        return $this;
    }

    /**
     * Get the age in human-readable format.
     */
    public function getAgeReadableAttribute(): string
    {
        $minutes = $this->age_minutes;

        if ($minutes < 60) {
            return $minutes . ' minute' . ($minutes !== 1 ? 's' : '');
        }

        $hours = (int) floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 24) {
            $result = $hours . ' hour' . ($hours !== 1 ? 's' : '');
            if ($remainingMinutes > 0) {
                $result .= ', ' . $remainingMinutes . ' minute' . ($remainingMinutes !== 1 ? 's' : '');
            }
            return $result;
        }

        $days = (int) floor($hours / 24);
        $remainingHours = $hours % 24;
        $result = $days . ' day' . ($days !== 1 ? 's' : '');
        if ($remainingHours > 0) {
            $result .= ', ' . $remainingHours . ' hour' . ($remainingHours !== 1 ? 's' : '');
        }
        return $result;
    }
}
