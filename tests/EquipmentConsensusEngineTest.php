<?php

namespace SuperInstance\Equipment\ConsensusEngine\Test;

use SuperInstance\Equipment\ConsensusEngine\EquipmentConsensusEngine;
use SuperInstance\Equipment\ConsensusEngine\DomainType;
use SuperInstance\Equipment\ConsensusEngine\PerspectiveType;
use PHPUnit\Framework\TestCase;

class EquipmentConsensusEngineTest extends TestCase
{
    private $engine;

    protected function setUp(): void
    {
        $this->engine = new EquipmentConsensusEngine();
    }

    public function testWeightedPerspectiveCalculation(): void
    {
        $perspectives = [
            ['weight' => 0.5, 'value' => 10],
            ['weight' => 0.3, 'value' => 20],
            ['weight' => 0.2, 'value' => 5],
        ];
        
        $result = $this->engine->calculateWeightedPerspective($perspectives);
        $this->assertEqualsWithDelta(11.5, $result, 0.01);
    }

    public function testTripartiteDeliberation(): void
    {
        $state = [
            'domain' => DomainType::FISHING,
            'perspectives' => [
                PerspectiveType::CREW_MEMBER => ['score' => 7.0, 'confidence' => 0.8],
                PerspectiveType::VESSEL_OWNER => ['score' => 8.0, 'confidence' => 0.6],
                PerspectiveType::ENVIRONMENT => ['score' => 6.5, 'confidence' => 0.7],
            ]
        ];
        
        $result = $this->engine->deliberate($state);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('consensus_score', $result);
        $this->assertArrayHasKey('confidence', $result);
    }

    public function testConflictResolution(): void
    {
        $conflicting = [
            ['score' => 9.0, 'domain' => 'gear', 'perspective' => 'crew'],
            ['score' => 3.0, 'domain' => 'gear', 'perspective' => 'owner'],
        ];
        
        $resolved = $this->engine->resolveConflict($conflicting);
        $this->assertGreaterThanOrEqual(0, $resolved['score']);
        $this->assertLessThanOrEqual(10, $resolved['score']);
        $this->assertArrayHasKey('resolution_type', $resolved);
    }

    public function testConsensusThreshold(): void
    {
        $votes = [9.0, 8.5, 9.2, 8.8, 9.1];
        $this->assertTrue($this->engine->achievesConsensus($votes, 0.5));
        
        $votes = [3.0, 8.5, 2.0, 7.0, 4.0];
        $this->assertFalse($this->engine->achievesConsensus($votes, 0.5));
    }
}
