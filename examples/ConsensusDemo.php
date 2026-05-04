<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SuperInstance\Equipment\ConsensusEngine\EquipmentConsensusEngine;
use SuperInstance\Equipment\ConsensusEngine\DomainType;
use SuperInstance\Equipment\ConsensusEngine\PerspectiveType;

// Demo: tripartite deliberation in action
$engine = new EquipmentConsensusEngine();

$state = [
    'domain' => DomainType::FISHING,
    'perspectives' => [
        PerspectiveType::CREW_MEMBER => ['score' => 8.0, 'confidence' => 0.9],
        PerspectiveType::VESSEL_OWNER => ['score' => 7.0, 'confidence' => 0.7],
        PerspectiveType::ENVIRONMENT => ['score' => 6.5, 'confidence' => 0.8],
    ]
];

$result = $engine->deliberate($state);

echo "Consensus Score: " . $result['consensus_score'] . "\n";
echo "Confidence: " . $result['confidence'] . "\n";
echo "Resolution: " . ($result['resolution_type'] ?? 'unknown') . "\n";