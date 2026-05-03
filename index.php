<?php
declare(strict_types=1);

/**
 * Equipment-Consensus-Engine
 * 
 * Multi-agent deliberation with Pathos/Logos/Ethos weighting
 * 
 * @package SuperInstance\Equipment\ConsensusEngine
 */

require_once __DIR__ . '/src/PerspectiveType.php';
require_once __DIR__ . '/src/DomainType.php';
require_once __DIR__ . '/src/types.php';
require_once __DIR__ . '/src/WeightCalculator.php';
require_once __DIR__ . '/src/TripartiteDeliberation.php';
require_once __DIR__ . '/src/ConflictResolution.php';
require_once __DIR__ . '/src/ConsensusEngine.php';

// Re-export main class for convenience
use SuperInstance\Equipment\ConsensusEngine\ConsensusEngine;
use SuperInstance\Equipment\ConsensusEngine\ConsensusEngineConfig;
use SuperInstance\Equipment\ConsensusEngine\DeliberationInput;
use SuperInstance\Equipment\ConsensusEngine\ConsensusResult;
use SuperInstance\Equipment\ConsensusEngine\PerspectiveType;
use SuperInstance\Equipment\ConsensusEngine\DomainType;