<?php
declare(strict_types=1);

namespace SuperInstance\Equipment\ConsensusEngine;

/**
 * Types of tripartite perspectives
 */
enum PerspectiveType: string
{
    case PATHOS = 'pathos';
    case LOGOS = 'logos';
    case ETHOS = 'ethos';

    public function label(): string
    {
        return match ($this) {
            self::PATHOS => 'Pathos (Emotional/Intent)',
            self::LOGOS => 'Logos (Logical/Reason)',
            self::ETHOS => 'Ethos (Ethical/Truth)',
        };
    }
}