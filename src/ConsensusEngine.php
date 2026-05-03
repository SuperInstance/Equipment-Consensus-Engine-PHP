<?php
declare(strict_types=1);

namespace SuperInstance\Equipment\ConsensusEngine;

use SuperInstance\Equipment\ConsensusEngine\TripartiteDeliberation;
use SuperInstance\Equipment\ConsensusEngine\WeightCalculator;
use SuperInstance\Equipment\ConsensusEngine\ConflictResolution;

/**
 * ConsensusEngine - Main equipment class for multi-agent deliberation
 * 
 * Coordinates the tripartite deliberation process across Pathos (intent/emotion),
 * Logos (logic/reason), and Ethos (truth/ethics) perspectives to build consensus.
 *
 * @package SuperInstance\Equipment\ConsensusEngine
 */
class ConsensusEngine
{
    private ConsensusEngineConfig $config;
    /** @var AuditEntry[] */
    private array $auditTrail = [];
    private int $auditIdCounter = 0;
    private TripartiteDeliberation $deliberation;
    private WeightCalculator $weightCalculator;
    private ConflictResolution $conflictResolution;

    /**
     * Creates a new ConsensusEngine instance
     */
    public function __construct(?ConsensusEngineConfig $config = null)
    {
        $this->config = $config ?? ConsensusEngineConfig::default();
        $this->deliberation = new TripartiteDeliberation($this->weightCalculator);
        $this->weightCalculator = new WeightCalculator($this->config->customWeights);
        $this->conflictResolution = new ConflictResolution();
        $this->addAuditEntry(AuditAction::DELIBERATION_START, 'ConsensusEngine initialized', ['config' => $this->config]);
    }

    /**
     * Conducts a deliberation on a proposition to reach consensus
     *
     * @param DeliberationInput $input The deliberation input containing proposition and context
     * @return ConsensusResult The consensus result
     */
    public function deliberate(DeliberationInput $input): ConsensusResult
    {
        $startTime = hrtime(true);
        $domain = $input->domainOverride ?? $this->config->domain;

        $this->addAuditEntry(
            AuditAction::DELIBERATION_START,
            "Starting deliberation on: {$input->proposition}",
            ['domain' => $domain->value, 'context' => $input->context]
        );

        $rounds = [];
        $resolvedConflicts = [];
        $consensusReached = false;
        $finalOpinions = [];

        // Conduct deliberation rounds
        for ($roundNum = 1; $roundNum <= $this->config->maxRounds && !$consensusReached; $roundNum++) {
            $round = $this->conductRound($roundNum, $input, $domain, $finalOpinions);
            $rounds[] = $round;

            // Detect conflicts
            $conflicts = $this->detectConflicts($round->opinions);
            if (count($conflicts) > 0) {
                $this->addAuditEntry(
                    AuditAction::CONFLICT_DETECTED,
                    "Detected " . count($conflicts) . " conflicts in round {$roundNum}",
                    ['conflicts' => array_map(fn($c) => ['type' => $c->type->value, 'perspectives' => $c->parties], $conflicts)]
                );

                foreach ($conflicts as $conflict) {
                    $resolution = $this->conflictResolution->resolve($conflict, $round->opinions);
                    $resolvedConflicts[] = $resolution;

                    $this->addAuditEntry(
                        AuditAction::CONFLICT_RESOLVED,
                        "Resolved conflict: {$conflict->type->value}",
                        ['strategy' => $resolution->resolved, 'outcome' => $resolution->verdict]
                    );
                }
            }

            // Evaluate consensus
            $consensusReached = $this->evaluateConsensus($round->opinions);

            if ($consensusReached) {
                $this->addAuditEntry(
                    AuditAction::CONSENSUS_REACHED,
                    "Consensus reached in round {$roundNum}",
                    ['consensusScore' => $round->interimScore]
                );
            }

            $finalOpinions = $round->opinions;
        }

        // Synthesize verdict and confidence
        $verdict = $this->synthesizeVerdict($finalOpinions, $consensusReached);
        $confidence = $this->calculateOverallConfidence($finalOpinions, $consensusReached);

        $dissentingOpinions = null;
        if ($this->config->includeDissent) {
            $dissentingOpinions = array_values(array_filter(
                $finalOpinions,
                fn($op) => $op->confidence < $this->config->confidenceThreshold
            ));
        }

        $durationMs = (int)((hrtime(true) - $startTime) / 1_000_000);

        $this->addAuditEntry(
            AuditAction::CONSENSUS_REACHED,
            'Deliberation completed',
            ['consensusReached' => $consensusReached, 'verdict' => $verdict, 'confidence' => $confidence, 'durationMs' => $durationMs]
        );

        $weightProfile = $this->weightCalculator->getProfile($domain);

        return new ConsensusResult(
            consensus: $consensusReached,
            verdict: $verdict,
            confidence: $confidence,
            perspectives: $finalOpinions,
            rounds: $rounds,
            resolvedConflicts: $resolvedConflicts,
            dissentingOpinions: $dissentingOpinions,
            auditTrail: $this->auditTrail,
            metadata: new ConsensusMetadata(
                durationMs: $durationMs,
                roundsCompleted: count($rounds),
                forcedConsensus: !$consensusReached,
                domain: $domain,
                weightProfile: $weightProfile,
                conflictsResolved: count($resolvedConflicts),
            )
        );
    }

    /**
     * Conduct a single deliberation round
     */
    private function conductRound(int $roundNum, DeliberationInput $input, DomainType $domain, array $previousOpinions): DeliberationRound
    {
        $weightProfile = $this->weightCalculator->getProfile($domain);
        $opinions = [];

        // Gather opinions from each perspective
        foreach ([PerspectiveType::PATHOS, PerspectiveType::LOGOS, PerspectiveType::ETHOS] as $type) {
            $analysis = $this->deliberation->analyze($type, $input->proposition, $input->context, $previousOpinions);
            $weight = $this->weightCalculator->getWeight($type, $weightProfile);

            $opinions[] = new PerspectiveOpinion(
                perspective: $type,
                verdict: $analysis->verdict,
                confidence: $analysis->confidence,
                arguments: $analysis->arguments,
                concerns: $analysis->concerns,
                weight: $weight,
            );
        }

        // Conduct cross-examinations
        $crossExaminations = $this->conductCrossExaminations($opinions);

        // Calculate interim score
        $interimScore = $this->calculateRoundScore($opinions);

        $round = new DeliberationRound(
            roundNumber: $roundNum,
            opinions: $opinions,
            crossExaminations: $crossExaminations,
            consensusReached: false,
            interimScore: $interimScore,
        );

        $this->addAuditEntry(
            AuditAction::ROUND_COMPLETE,
            "Round {$roundNum} completed",
            ['opinions' => count($opinions), 'interimScore' => $interimScore]
        );

        return $round;
    }

    /**
     * Conduct cross-examinations between perspectives
     *
     * @param PerspectiveOpinion[] $opinions
     * @return CrossExamination[]
     */
    private function conductCrossExaminations(array $opinions): array
    {
        $examinations = [];
        $pairs = [
            [PerspectiveType::PATHOS, PerspectiveType::LOGOS],
            [PerspectiveType::LOGOS, PerspectiveType::ETHOS],
            [PerspectiveType::ETHOS, PerspectiveType::PATHOS],
        ];

        foreach ($pairs as [$challenger, $responder]) {
            $challengerOp = $this->findOpinion($opinions, $challenger);
            $responderOp = $this->findOpinion($opinions, $responder);

            if (!$challengerOp || !$responderOp) continue;

            $challenge = $this->generateChallenge($challengerOp, $responderOp);
            $response = $this->generateResponse($responderOp, $challenge);
            $satisfactory = $this->evaluateResponse($response, $responderOp);

            $examinations[] = new CrossExamination(
                challenger: $challenger,
                responder: $responder,
                challenge: $challenge,
                response: $response,
                satisfactory: $satisfactory,
                impact: $satisfactory ? 0.05 : -0.05,
            );
        }

        return $examinations;
    }

    /**
     * Detect conflicts between perspectives
     *
     * @param PerspectiveOpinion[] $opinions
     * @return ConflictRecord[]
     */
    private function detectConflicts(array $opinions): array
    {
        $conflicts = [];

        $pathos = $this->findOpinion($opinions, PerspectiveType::PATHOS);
        $logos = $this->findOpinion($opinions, PerspectiveType::LOGOS);
        $ethos = $this->findOpinion($opinions, PerspectiveType::ETHOS);

        // Check for logical contradiction (logos vs pathos tension)
        if ($pathos && $logos) {
            $tension = abs($pathos->confidence - $logos->confidence);
            if ($tension > 0.4) {
                $conflicts[] = new ConflictRecord(
                    type: ConflictType::EMOTIONAL_TENSION,
                    severity: $tension > 0.7 ? ConflictSeverity::HIGH : ConflictSeverity::MEDIUM,
                    parties: [PerspectiveType::PATHOS->value, PerspectiveType::LOGOS->value],
                    description: "Pathos and Logos perspectives are in tension: emotional vs logical",
                );
            }
        }

        // Check for ethical dilemma (ethos vs other)
        if ($ethos) {
            foreach ([$pathos, $logos] as $other) {
                if (!$other) continue;
                $tension = abs($ethos->confidence - $other->confidence);
                if ($tension > 0.6 && $ethos->confidence < 0.4) {
                    $conflicts[] = new ConflictRecord(
                        type: ConflictType::ETHICAL_DILEMMA,
                        severity: ConflictSeverity::HIGH,
                        parties: [PerspectiveType::ETHOS->value, $other->perspective->value],
                        description: "Ethos concerns not adequately addressed",
                    );
                }
            }
        }

        return $conflicts;
    }

    /**
     * Evaluate whether consensus has been reached
     *
     * @param PerspectiveOpinion[] $opinions
     */
    private function evaluateConsensus(array $opinions): bool
    {
        if (count($opinions) < 2) return false;

        $totalWeight = 0.0;
        $weightedConf = 0.0;

        foreach ($opinions as $op) {
            $weightedConf += $op->confidence * $op->weight;
            $totalWeight += $op->weight;
        }

        if ($totalWeight === 0.0) return false;

        $averageConfidence = $weightedConf / $totalWeight;
        return $averageConfidence >= $this->config->confidenceThreshold;
    }

    /**
     * Calculate overall confidence across all perspectives
     *
     * @param PerspectiveOpinion[] $opinions
     */
    private function calculateOverallConfidence(array $opinions, bool $consensusReached): float
    {
        if (count($opinions) === 0) return 0.0;

        $totalWeight = 0.0;
        $weightedConf = 0.0;

        foreach ($opinions as $op) {
            $weightedConf += $op->confidence * $op->weight;
            $totalWeight += $op->weight;
        }

        if ($totalWeight === 0.0) return 0.0;

        $avg = $weightedConf / $totalWeight;
        return $consensusReached ? $avg : $avg * 0.8;
    }

    /**
     * Synthesize a final verdict from all perspectives
     *
     * @param PerspectiveOpinion[] $opinions
     */
    private function synthesizeVerdict(array $opinions, bool $consensusReached): string
    {
        if (count($opinions) === 0) return 'No verdict reached';

        if ($consensusReached) {
            // Return the highest-weighted opinion's verdict
            usort($opinions, fn($a, $b) => $b->weight <=> $a->weight);
            return $opinions[0]->verdict;
        }

        // Synthesize from all perspectives
        $parts = [];
        foreach ($opinions as $op) {
            if (!empty($op->verdict)) {
                $parts[] = "[{$op->perspective->value}] {$op->verdict}";
            }
        }

        return implode(' | ', $parts);
    }

    private function calculateRoundScore(array $opinions): float
    {
        $totalWeight = 0.0;
        $weightedConf = 0.0;
        foreach ($opinions as $op) {
            $weightedConf += $op->confidence * $op->weight;
            $totalWeight += $op->weight;
        }
        return $totalWeight > 0 ? $weightedConf / $totalWeight : 0.0;
    }

    private function findOpinion(array $opinions, PerspectiveType $type): ?PerspectiveOpinion
    {
        foreach ($opinions as $op) {
            if ($op->perspective === $type) return $op;
        }
        return null;
    }

    private function generateChallenge(PerspectiveOpinion $challenger, PerspectiveOpinion $responder): string
    {
        return "How does your {$responder->perspective->value} perspective account for: {$challenger->verdict}";
    }

    private function generateResponse(PerspectiveOpinion $responder, string $challenge): string
    {
        return "From {$responder->perspective->label()}: {$responder->verdict}. This addresses the concern through {$responder->perspective->value} reasoning.";
    }

    private function evaluateResponse(string $response, PerspectiveOpinion $responder): bool
    {
        return $responder->confidence >= $this->config->confidenceThreshold * 0.8;
    }

    private function addAuditEntry(AuditAction $action, string $description, ?array $data = null): void
    {
        if (!$this->config->enableAudit) return;
        $this->auditTrail[] = AuditEntry::generate($action, $description, $data);
    }

    public function getConfig(): ConsensusEngineConfig
    {
        return $this->config;
    }

    public function getAuditTrail(): array
    {
        return $this->auditTrail;
    }
}