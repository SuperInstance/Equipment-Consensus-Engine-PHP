<?php
declare(strict_types=1);

namespace SuperInstance\Equipment\ConsensusEngine;

use DateTimeImmutable;
use Exception;

/**
 * Configuration for the ConsensusEngine
 */
 readonly class ConsensusEngineConfig
{
    public function __construct(
        public int    $maxRounds = 5,
        public float  $confidenceThreshold = 0.7,
        public bool   $includeDissent = true,
        public DomainType $domain = DomainType::BALANCED,
        public bool   $enableAudit = true,
        public int    $timeout = 30000,
        public ?WeightProfile $customWeights = null,
    ) {}

    public static function default(): self
    {
        return new self();
    }

    public function withMaxRounds(int $rounds): self
    {
        return new self(
            maxRounds: $rounds,
            confidenceThreshold: $this->confidenceThreshold,
            includeDissent: $this->includeDissent,
            domain: $this->domain,
            enableAudit: $this->enableAudit,
            timeout: $this->timeout,
            customWeights: $this->customWeights,
        );
    }

    public function withDomain(DomainType $domain): self
    {
        return new self(
            maxRounds: $this->maxRounds,
            confidenceThreshold: $this->confidenceThreshold,
            includeDissent: $this->includeDissent,
            domain: $domain,
            enableAudit: $this->enableAudit,
            timeout: $this->timeout,
            customWeights: $this->customWeights,
        );
    }
}

/**
 * Input for a deliberation request
 */
 readonly class DeliberationInput
{
    public function __construct(
        public string     $proposition,
        public string     $context,
        public ?DomainType $domainOverride = null,
        public ?array     $metadata = null,
    ) {}

    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }
}

/**
 * A single perspective's opinion on a proposition
 */
 readonly class PerspectiveOpinion
{
    public function __construct(
        public PerspectiveType $perspective,
        public string     $verdict,
        public float      $confidence,
        public array      $arguments = [],
        public array      $concerns = [],
        public float      $weight = 0.0,
        public ?DateTimeImmutable $timestamp = null,
    ) {
        $this->timestamp ??= new DateTimeImmutable();
    }

    public function getConfidenceCategory(): string
    {
        if ($this->confidence >= 0.8) return 'high';
        if ($this->confidence >= 0.5) return 'medium';
        return 'low';
    }

    public function hasStrongConcerns(): bool
    {
        return count($this->concerns) > 0;
    }
}

/**
 * Cross-examination between two perspectives
 */
 readonly class CrossExamination
{
    public function __construct(
        public PerspectiveType $challenger,
        public PerspectiveType $responder,
        public string     $challenge,
        public string     $response,
        public bool       $satisfactory = false,
        public float      $impact = 0.0,
    ) {}
}

/**
 * A record of a single deliberation round
 */
 readonly class DeliberationRound
{
    public function __construct(
        public int                $roundNumber,
        public array               $opinions = [],
        public array               $crossExaminations = [],
        public bool               $consensusReached = false,
        public ?float             $interimScore = null,
        public ?DateTimeImmutable $timestamp = null,
    ) {
        $this->timestamp ??= new DateTimeImmutable();
    }

    public function hasOpinionsFrom(PerspectiveType $type): bool
    {
        foreach ($this->opinions as $op) {
            if ($op->perspective === $type) return true;
        }
        return false;
    }
}

/**
 * Types of audit trail entries
 */
enum AuditAction: string
{
    case DELIBERATION_START = 'deliberation_start';
    case ROUND_COMPLETE = 'round_complete';
    case CONFLICT_DETECTED = 'conflict_detected';
    case CONFLICT_RESOLVED = 'conflict_resolved';
    case CONSENSUS_REACHED = 'consensus_reached';
    case TIMEOUT = 'timeout';
    case ERROR = 'error';
}

/**
 * An entry in the audit trail
 */
 readonly class AuditEntry
{
    public function __construct(
        public string           $id,
        public DateTimeImmutable $timestamp,
        public AuditAction      $action,
        public string           $description,
        public ?array           $data = null,
    ) {}

    public static function generate(AuditAction $action, string $description, ?array $data = null): self
    {
        return new self(
            id: bin2hex(random_bytes(8)),
            timestamp: new DateTimeImmutable(),
            action: $action,
            description: $description,
            data: $data,
        );
    }
}

/**
 * Metadata about the consensus result
 */
 readonly class ConsensusMetadata
{
    public function __construct(
        public int          $durationMs,
        public int          $roundsCompleted,
        public bool         $forcedConsensus,
        public DomainType   $domain,
        public WeightProfile $weightProfile,
        public int          $conflictsResolved = 0,
    ) {}

    public function getAverageRoundDuration(): float
    {
        if ($this->roundsCompleted === 0) return 0.0;
        return $this->durationMs / $this->roundsCompleted;
    }
}

/**
 * The final result of a consensus deliberation
 */
 readonly class ConsensusResult
{
    public function __construct(
        public bool                  $consensus,
        public string                $verdict,
        public float                 $confidence,
        public array                 $perspectives = [],
        public array                 $rounds = [],
        public array                 $resolvedConflicts = [],
        public ?array                $dissentingOpinions = null,
        public array                 $auditTrail = [],
        public ConsensusMetadata     $metadata,
    ) {}

    public function getDominantPerspective(): ?PerspectiveType
    {
        $max = 0.0;
        $dominant = null;
        foreach ($this->perspectives as $p) {
            if ($p->confidence > $max) {
                $max = $p->confidence;
                $dominant = $p->perspective;
            }
        }
        return $dominant;
    }

    public function wasForced(): bool
    {
        return $this->metadata->forcedConsensus;
    }
}

/**
 * Conflict types detected during deliberation
 */
enum ConflictType: string
{
    case LOGICAL_CONTRADICTION = 'logical_contradiction';
    case EMOTIONAL_TENSION = 'emotional_tension';
    case ETHICAL_DILEMMA = 'ethical_dilemma';
    case VALUE_CONFLICT = 'value_conflict';
    case PRIORITY_CONFLICT = 'priority_conflict';
    case RESOURCE_CONFLICT = 'resource_conflict';
}

/**
 * Severity of a conflict
 */
enum ConflictSeverity: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';
}

/**
 * Strategy for resolving conflicts
 */
enum ResolutionStrategy: string
{
    case WEIGHTED_VOTING = 'weighted_voting';
    case DELIBERATION_EXTENSION = 'deliberation_extension';
    case REFRAMING = 'reframing';
    case COMPROMISE = 'compromise';
    case PERSPECTIVE_DOMINANCE = 'perspective_dominance';
    case CONDITIONAL_APPROVAL = 'conditional_approval';
    case SUSPENSION = 'suspension';
    case ESCALATION = 'escalation';
    case MEDIATE = 'mediate';
    case VOTE = 'vote';
    case HIERARCHY = 'hierarchy';
    case DELEGATE = 'delegate';
    case DEFER = 'defer';
    case RECONSIDER = 'reconsider';
    case ACCEPT_LOSS = 'accept_loss';
}

/**
 * A record of a detected conflict
 */
 readonly class ConflictRecord
{
    public function __construct(
        public ConflictType     $type,
        public ConflictSeverity $severity,
        public array            $parties = [],
        public string           $description = '',
        public ?ResolutionStrategy $strategy = null,
    ) {}
}

/**
 * Result of resolving a conflict
 */
 readonly class ResolutionResult
{
    public function __construct(
        public bool             $resolved,
        public ?string          $verdict = null,
        public ?float           $agreementLevel = null,
        public array            $terms = [],
        public array            $remainingDisagreements = [],
        public ?ConflictRecord   $originalConflict = null,
    ) {}
}