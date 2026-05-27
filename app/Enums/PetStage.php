<?php

namespace App\Enums;

enum PetStage: string
{
    case Egg = 'egg';
    case Baby = 'baby';
    case Child = 'child';
    case Teen = 'teen';
    case Adult = 'adult';

    /**
     * Determine the stage based on age in minutes.
     */
    public static function fromAge(int $ageMinutes): self
    {
        return match (true) {
            $ageMinutes < 5 => self::Egg,
            $ageMinutes < 60 => self::Baby,
            $ageMinutes < 360 => self::Child,
            $ageMinutes < 1440 => self::Teen,
            default => self::Adult,
        };
    }
}
