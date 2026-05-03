# Equipment-Consensus-Engine (PHP)

Multi-agent deliberation with Pathos/Logos/Ethos weighting for consensus building.

**Ported from TypeScript** — full fidelity mechanical port preserving all original behavior.

## Installation

```bash
composer require superinstance/equipment-consensus-engine
```

Or with a private repository:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/SuperInstance/Equipment-Consensus-Engine-PHP"
        }
    ],
    "require": {
        "superinstance/equipment-consensus-engine": "^1.0.0"
    }
}
```

## Quick Start

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use SuperInstance\Equipment\ConsensusEngine\ConsensusEngine;
use SuperInstance\Equipment\ConsensusEngine\ConsensusEngineConfig;
use SuperInstance\Equipment\ConsensusEngine\DeliberationInput;
use SuperInstance\Equipment\ConsensusEngine\DomainType;

// Create engine with default configuration
$engine = new ConsensusEngine();

// Run deliberation
$result = $engine->deliberate(new DeliberationInput(
    proposition: 'Should we implement feature X?',
    context: 'Given our current resources and timeline...'
));

echo "Consensus: " . ($result->consensus ? 'YES' : 'NO') . "\n";
echo "Verdict: {$result->verdict}\n";
echo "Confidence: " . round($result->confidence * 100) . "%\n";
```

## API Reference

### ConsensusEngine

```php
$config = new ConsensusEngineConfig(
    maxRounds: 5,
    confidenceThreshold: 0.7,
    includeDissent: true,
    domain: DomainType::BALANCED,
    enableAudit: true,
    timeout: 30000,
);

$engine = new ConsensusEngine($config);
$result = $engine->deliberate($input);
```

### Domain Types

10 predefined domains: `factual`, `emotional`, `sensitive`, `creative`, `balanced`, `technical`, `social`, `business`, `personal`, `maritime`

### Perspective Types

- **PATHOS** — Emotional/intent perspective
- **LOGOS** — Logical/reason perspective  
- **ETHOS** — Ethical/truth perspective

## Architecture

```
DeliberationInput
       ↓
ConsensusEngine ←→ WeightCalculator
       ↓                ↓
TripartiteDeliberation → analyzes each perspective
       ↓
ConflictResolution → resolves disagreements
       ↓
ConsensusResult → final verdict with metadata
```

## Requirements

- PHP 8.0+
- No external dependencies (pure PHP)

## License

MIT