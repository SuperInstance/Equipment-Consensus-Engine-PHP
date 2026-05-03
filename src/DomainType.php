<?php
declare(strict_types=1);

namespace SuperInstance\Equipment\ConsensusEngine;

/**
 * Domain types for weight calculation
 */
enum DomainType: string
{
    case FACTUAL = 'factual';
    case EMOTIONAL = 'emotional';
    case SENSITIVE = 'sensitive';
    case CREATIVE = 'creative';
    case BALANCED = 'balanced';
    case TECHNICAL = 'technical';
    case SOCIAL = 'social';
    case BUSINESS = 'business';
    case PERSONAL = 'personal';
    case MARITIME = 'maritime';

    public function label(): string
    {
        return match ($this) {
            self::FACTUAL => 'Scientific/Technical',
            self::EMOTIONAL => 'Human-Centered',
            self::SENSITIVE => 'Ethically Complex',
            self::CREATIVE => 'Artistic/Innovative',
            self::BALANCED => 'Default Balanced',
            self::TECHNICAL => 'Engineering',
            self::SOCIAL => 'Community Impact',
            self::BUSINESS => 'Commercial/Strategic',
            self::PERSONAL => 'Individual Focus',
            self::MARITIME => 'Vessel/Fleet Operations',
        };
    }
}