<?php
declare(strict_types=1);

namespace SuperInstance\Equipment\ConsensusEngine;

/**
 * ConflictResolution - Resolve disagreements between perspectives
 * 
 * Implements strategies for detecting and resolving conflicts when
 * Pathos, Logos, and Ethos perspectives disagree on a proposition.
 *
 * @package SuperInstance\Equipment\ConsensusEngine
 */
class ConflictResolution
{
    /**
     * Resolves a conflict between perspectives
     *
     * @param ConflictRecord $conflict The conflict to resolve
     * @param PerspectiveOpinion[] $opinions The current opinions
     * @return ResolutionResult The resolution result
     */
    public function resolve(ConflictRecord $conflict, array $opinions): ResolutionResult
    {
        $strategy = $this->selectStrategy($conflict, $opinions);
        $outcome = $this->applyStrategy($strategy, $conflict, $opinions);

        return new ResolutionResult(
            resolved: $outcome['resolved'],
            verdict: $outcome['verdict'] ?? null,
            agreementLevel: $outcome['agreementLevel'] ?? null,
            terms: $outcome['terms'] ?? [],
            remainingDisagreements: $outcome['remainingDisagreements'] ?? [],
            originalConflict: $conflict,
        );
    }

    /**
     * Selects the best resolution strategy for a conflict
     */
    private function selectStrategy(ConflictRecord $conflict, array $opinions): ResolutionStrategy
    {
        // Try to find consensus through weighted voting first
        $weightedScore = $this->calculateWeightedScore($opinions);
        
        if ($weightedScore !== null && $weightedScore > 0.7) {
            return ResolutionStrategy::WEIGHTED_VOTING;
        }

        // Check severity
        $severity = $conflict->severity;

        if ($severity === ConflictSeverity::CRITICAL) {
            return ResolutionStrategy::DELIBERATION_EXTENSION;
        }

        if ($severity === ConflictSeverity::HIGH) {
            // High severity: try deliberation extension or compromise
            return ResolutionStrategy::COMPROMISE;
        }

        if ($severity === ConflictSeverity::MEDIUM) {
            return ResolutionStrategy::REFRAMING;
        }

        return ResolutionStrategy::WEIGHTED_VOTING;
    }

    /**
     * Applies a resolution strategy
     */
    private function applyStrategy(ResolutionStrategy $strategy, ConflictRecord $conflict, array $opinions): array
    {
        return match ($strategy) {
            ResolutionStrategy::WEIGHTED_VOTING => $this->applyWeightedVoting($conflict, $opinions),
            ResolutionStrategy::DELIBERATION_EXTENSION => $this->applyDeliberationExtension($conflict, $opinions),
            ResolutionStrategy::REFRAMING => $this->applyReframing($conflict, $opinions),
            ResolutionStrategy::COMPROMISE => $this->applyCompromise($conflict, $opinions),
            ResolutionStrategy::PERSPECTIVE_DOMINANCE => $this->applyPerspectiveDominance($conflict, $opinions),
            ResolutionStrategy::CONDITIONAL_APPROVAL => $this->applyConditionalApproval($conflict, $opinions),
            ResolutionStrategy::SUSPENSION => $this->applySuspension($conflict, $opinions),
            default => $this->applyWeightedVoting($conflict, $opinions),
        };
    }

    private function applyWeightedVoting(ConflictRecord $conflict, array $opinions): array
    {
        $weightedScore = $this->calculateWeightedScore($opinions);

        if ($weightedScore === null) {
            return [
                'resolved' => false,
                'verdict' => null,
                'agreementLevel' => 0.0,
                'terms' => [],
                'remainingDisagreements' => array_map(fn($p) => "{$p->perspective->value} disagrees", $opinions),
            ];
        }

        // Find the winning opinion
        $winner = null;
        $maxScore = -1;
        foreach ($opinions as $op) {
            $score = $op->confidence * $op->weight;
            if ($score > $maxScore) {
                $maxScore = $score;
                $winner = $op;
            }
        }

        $accepted = array_filter($opinions, fn($op) => abs($op->confidence * $op->weight - $maxScore) < 0.1);
        $rejected = array_filter($opinions, fn($op) => abs($op->confidence * $op->weight - $maxScore) >= 0.1);

        return [
            'resolved' => true,
            'verdict' => $winner?->verdict ?? 'No verdict',
            'agreementLevel' => $weightedScore,
            'terms' => ['Winner determined by weighted confidence'],
            'remainingDisagreements' => array_map(fn($op) => "{$op->perspective->value}: {$op->verdict}", $rejected),
            'acceptedBy' => $accepted,
            'rejectedBy' => $rejected,
        ];
    }

    private function applyDeliberationExtension(ConflictRecord $conflict, array $opinions): array
    {
        return [
            'resolved' => false,
            'verdict' => null,
            'agreementLevel' => 0.3,
            'terms' => ['Additional deliberation rounds recommended'],
            'remainingDisagreements' => array_map(fn($op) => "{$op->perspective->value} requires more deliberation", $opinions),
        ];
    }

    private function applyReframing(ConflictRecord $conflict, array $opinions): array
    {
        return [
            'resolved' => true,
            'verdict' => 'Reframe the proposition and reconsider',
            'agreementLevel' => 0.5,
            'terms' => ['Proposition should be reframed to address ' . implode(', ', $conflict->parties)],
            'remainingDisagreements' => [],
        ];
    }

    private function applyCompromise(ConflictRecord $conflict, array $opinions): array
    {
        // Find a compromise verdict
        $verdicts = array_map(fn($op) => $op->verdict, $opinions);
        $compromised = 'Compromise: ' . implode(' + ', array_slice(array_unique($verdicts), 0, 2));

        return [
            'resolved' => true,
            'verdict' => $compromised,
            'agreementLevel' => 0.6,
            'terms' => ['Each perspective gives ground to reach agreement'],
            'remainingDisagreements' => ['Minor reservations remain from ' . implode(', ', array_map(fn($p) => $p->perspective->value, array_filter($opinions, fn($op) => $op->confidence < 0.5)))],
        ];
    }

    private function applyPerspectiveDominance(ConflictRecord $conflict, array $opinions): array
    {
        // Find dominant perspective by weight
        $dominant = null;
        $maxWeight = -1;
        foreach ($opinions as $op) {
            if ($op->weight > $maxWeight) {
                $maxWeight = $op->weight;
                $dominant = $op;
            }
        }

        return [
            'resolved' => true,
            'verdict' => $dominant?->verdict ?? 'Dominant perspective prevails',
            'agreementLevel' => $maxWeight,
            'terms' => ['Dominant perspective decision'],
            'remainingDisagreements' => array_map(fn($op) => "{$op->perspective->value} overruled", array_filter($opinions, fn($op) => $op !== $dominant)),
        ];
    }

    private function applyConditionalApproval(ConflictRecord $conflict, array $opinions): array
    {
        $winner = $this->findWinner($opinions);

        return [
            'resolved' => true,
            'verdict' => $winner?->verdict ?? 'Conditional approval granted',
            'agreementLevel' => 0.5,
            'terms' => [
                'Subject to monitoring',
                'Requires follow-up review',
                'May be revoked if concerns materialize',
            ],
            'remainingDisagreements' => ['Some concerns remain - conditional approval with monitoring'],
        ];
    }

    private function applySuspension(ConflictRecord $conflict, array $opinions): array
    {
        return [
            'resolved' => false,
            'verdict' => 'Decision suspended pending more information',
            'agreementLevel' => 0.0,
            'terms' => ['Suspended until additional data is available'],
            'remainingDisagreements' => array_map(fn($op) => "{$op->perspective->value}: {$op->verdict}", $opinions),
        ];
    }

    /**
     * Calculates weighted confidence score across all opinions
     */
    private function calculateWeightedScore(array $opinions): ?float
    {
        if (count($opinions) === 0) return null;

        $totalWeight = 0.0;
        $weightedConf = 0.0;

        foreach ($opinions as $op) {
            $weightedConf += $op->confidence * $op->weight;
            $totalWeight += $op->weight;
        }

        if ($totalWeight === 0.0) return null;

        return $weightedConf / $totalWeight;
    }

    private function findWinner(array $opinions): ?PerspectiveOpinion
    {
        $winner = null;
        $maxScore = -1;

        foreach ($opinions as $op) {
            $score = $op->confidence * $op->weight;
            if ($score > $maxScore) {
                $maxScore = $score;
                $winner = $op;
            }
        }

        return $winner;
    }
}