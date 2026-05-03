<?php
/**
 * Equipment-Consensus-Engine
 * Multi-agent deliberation with Pathos/Logos/Ethos weighting
 * 
 * Port of the TypeScript original to PHP
 * 
 * @package SuperInstance\Equipment\ConsensusEngine
 */

declare(strict_types=1);

namespace SuperInstance\Equipment\ConsensusEngine;

require_once __DIR__ . '/ConsensusEngine.php';
require_once __DIR__ . '/TripartiteDeliberation.php';
require_once __DIR__ . '/WeightCalculator.php';
require_once __DIR__ . '/ConflictResolution.php';

use SuperInstance\Equipment\ConsensusEngine\ConsensusEngine;
use SuperInstance\Equipment\ConsensusEngine\TripartiteDeliberation;
use SuperInstance\Equipment\ConsensusEngine\WeightCalculator;
use SuperInstance\Equipment\ConsensusEngine\ConflictResolution;

class EquipmentConsensusEngine
{
    private ConsensusEngine $engine;
    private TripartiteDeliberation $deliberation;
    private WeightCalculator $weights;
    private ConflictResolution $conflict;

    public function __construct(
        public readonly string $version = '1.0.0',
        ?ConsensusEngineConfig $config = null
    ) {
        $this->weights = new WeightCalculator();
        $this->deliberation = new TripartiteDeliberation($this->weights);
        $this->conflict = new ConflictResolution();
        $this->engine = new ConsensusEngine(
            $this->deliberation,
            $this->weights,
            $this->conflict,
            $config ?? ConsensusEngineConfig::default()
        );
    }

    public function deliberate(DeliberationInput $input): ConsensusResult
    {
        return $this->engine->buildConsensus($input);
    }

    public function withWeights(WeightProfile $profile): self
    {
        $this->weights->setProfile($profile);
        return $this;
    }

    public function withDomain(DomainType $domain): self
    {
        $this->weights->setDomain($domain);
        return $this;
    }
}
