<?php
declare(strict_types=1);

namespace SuperInstance\Equipment\ConsensusEngine;

/**
 * Analysis result from a perspective
 */
 readonly class PerspectiveAnalysis
{
    public function __construct(
        public PerspectiveType $perspective,
        public string     $verdict,
        public float      $confidence,
        public array      $arguments = [],
        public array      $concerns = [],
        public ?string    $emotionalTone = null,
        public ?float     $logicalValidity = null,
        public ?float     $ethicalAlignment = null,
        public array      $suggestions = [],
    ) {}
}

/**
 * TripartiteDeliberation - Manages deliberation across three rhetorical perspectives
 * 
 * The tripartite framework originates from Aristotle's Rhetoric and provides
 * a comprehensive approach to argumentation:
 * 
 * - **Pathos**: Appeals to emotion, intent, and human experience
 * - **Logos**: Appeals to logic, reason, and rational argument
 * - **Ethos**: Appeals to ethics, credibility, and moral character
 *
 * @package SuperInstance\Equipment\ConsensusEngine
 */
class TripartiteDeliberation
{
    /** @var array<PerspectiveType, array{threshold: float, style: string}> */
    private array $perspectiveConfigs = [];

    public function __construct(
        private readonly WeightCalculator $weightCalculator,
    ) {
        $this->perspectiveConfigs = [
            PerspectiveType::PATHOS->value => ['threshold' => 0.6, 'style' => 'collaborative'],
            PerspectiveType::LOGOS->value => ['threshold' => 0.7, 'style' => 'inquisitive'],
            PerspectiveType::ETHOS->value => ['threshold' => 0.75, 'style' => 'adversarial'],
        ];
    }

    /**
     * Analyzes a proposition from a specific perspective
     *
     * @return PerspectiveAnalysis
     */
    public function analyze(
        PerspectiveType $perspective,
        string $proposition,
        string $context,
        array $previousOpinions = []
    ): PerspectiveAnalysis {
        return match ($perspective) {
            PerspectiveType::PATHOS => $this->analyzeFromPathos($proposition, $context, $previousOpinions),
            PerspectiveType::LOGOS => $this->analyzeFromLogos($proposition, $context, $previousOpinions),
            PerspectiveType::ETHOS => $this->analyzeFromEthos($proposition, $context, $previousOpinions),
        };
    }

    /**
     * Analyzes from the Pathos perspective (emotion/intent)
     */
    private function analyzeFromPathos(string $proposition, string $context, array $previousOpinions): PerspectiveAnalysis
    {
        $combined = strtolower($proposition . ' ' . $context);

        // Detect primary emotion
        $emotions = ['hope', 'fear', 'joy', 'anger', 'sadness', 'surprise', 'trust', 'anticipation'];
        $detectedEmotions = array_filter($emotions, fn($e) => str_contains($combined, $e));
        $primaryEmotion = $detectedEmotions[0] ?? 'neutral';

        // Calculate emotional intensity
        $emotionalWords = ['urgent', 'critical', 'important', 'essential', 'vital', 'crucial'];
        $intensity = 0.3;
        foreach ($emotionalWords as $word) {
            if (str_contains($combined, $word)) {
                $intensity += 0.15;
            }
        }
        $intensity = min(1.0, $intensity);

        // Identify stakeholders
        $stakeholders = $this->identifyStakeholders($combined);
        $emotionalRisks = $this->identifyEmotionalRisks($combined);

        $previousPathos = $this->findOpinion($previousOpinions, PerspectiveType::PATHOS);
        $verdict = $this->generatePathosVerdict($proposition, $primaryEmotion, $intensity, $stakeholders, $previousPathos);
        $confidence = $this->calculatePathosConfidence($primaryEmotion, $intensity, $stakeholders, $previousOpinions);

        return new PerspectiveAnalysis(
            perspective: PerspectiveType::PATHOS,
            verdict: $verdict,
            confidence: $confidence,
            arguments: $this->generatePathosArguments($primaryEmotion, $intensity, $stakeholders, $emotionalRisks),
            concerns: $this->identifyPathosConcerns($intensity, count($stakeholders), $emotionalRisks),
            emotionalTone: $primaryEmotion,
            suggestions: $this->generatePathosSuggestions($primaryEmotion, $stakeholders),
        );
    }

    /**
     * Analyzes from the Logos perspective (logic/reason)
     */
    private function analyzeFromLogos(string $proposition, string $context, array $previousOpinions): PerspectiveAnalysis
    {
        $combined = strtolower($proposition . ' ' . $context);

        // Determine logical structure
        $structure = 'inductive';
        if (str_contains($combined, 'therefore') || str_contains($combined, 'thus') || str_contains($combined, 'must')) {
            $structure = 'deductive';
        } elseif (str_contains($combined, 'likely') || str_contains($combined, 'probably') || str_contains($combined, 'suggests')) {
            $structure = 'abductive';
        } elseif (str_contains($combined, 'like') || str_contains($combined, 'similar') || str_contains($combined, 'comparable')) {
            $structure = 'analogical';
        }

        $premises = $this->extractPremises($combined);
        $assumptions = $this->identifyAssumptions($combined);
        $logicalRisks = $this->identifyLogicalRisks($combined);

        $previousLogos = $this->findOpinion($previousOpinions, PerspectiveType::LOGOS);
        $verdict = $this->generateLogosVerdict($structure, count($premises), $assumptions, $logicalRisks, $previousLogos);
        $confidence = $this->calculateLogosConfidence(count($premises), count($assumptions), count($logicalRisks), $previousOpinions);

        return new PerspectiveAnalysis(
            perspective: PerspectiveType::LOGOS,
            verdict: $verdict,
            confidence: $confidence,
            arguments: $this->generateLogosArguments($structure, $premises, $assumptions),
            concerns: $this->identifyLogosConcerns(count($premises), count($assumptions), $logicalRisks),
            logicalValidity: $this->assessLogicalValidity(count($logicalRisks), count($assumptions)),
            suggestions: $this->generateLogosSuggestions($assumptions, count($premises)),
        );
    }

    /**
     * Analyzes from the Ethos perspective (ethics/truth)
     */
    private function analyzeFromEthos(string $proposition, string $context, array $previousOpinions): PerspectiveAnalysis
    {
        $combined = strtolower($proposition . ' ' . $context);

        // Determine ethical framework
        $framework = 'utilitarian';
        if (str_contains($combined, 'rights') || str_contains($combined, 'duty') || str_contains($combined, 'obligation')) {
            $framework = 'deontological';
        } elseif (str_contains($combined, 'virtue') || str_contains($combined, 'character') || str_contains($combined, 'integrity')) {
            $framework = 'virtue_ethics';
        } elseif (str_contains($combined, 'care') || str_contains($combined, 'relationship') || str_contains($combined, 'empathy')) {
            $framework = 'care_ethics';
        }

        $principles = $this->identifyPrinciples($combined);
        $valuesAtStake = $this->identifyValuesAtStake($combined);
        $ethicalRisks = $this->identifyEthicalRisks($combined);

        $previousEthos = $this->findOpinion($previousOpinions, PerspectiveType::ETHOS);
        $verdict = $this->generateEthosVerdict($framework, $principles, $valuesAtStake, $ethicalRisks, $previousEthos);
        $confidence = $this->calculateEthosConfidence(count($principles), count($ethicalRisks), $previousOpinions);

        return new PerspectiveAnalysis(
            perspective: PerspectiveType::ETHOS,
            verdict: $verdict,
            confidence: $confidence,
            arguments: $this->generateEthosArguments($framework, $principles, $valuesAtStake),
            concerns: $this->identifyEthosConcerns($ethicalRisks, count($valuesAtStake)),
            ethicalAlignment: $this->assessEthicalAlignment(count($ethicalRisks)),
            suggestions: $this->generateEthosSuggestions($ethicalRisks, $framework, count($valuesAtStake)),
        );
    }

    // ===== Pathos helpers =====

    private function identifyStakeholders(string $text): array
    {
        $stakeholders = [];
        if (str_contains($text, 'employee') || str_contains($text, 'worker') || str_contains($text, 'staff')) {
            $stakeholders[] = 'employees';
        }
        if (str_contains($text, 'customer') || str_contains($text, 'client') || str_contains($text, 'user')) {
            $stakeholders[] = 'customers';
        }
        if (str_contains($text, 'community') || str_contains($text, 'society') || str_contains($text, 'public')) {
            $stakeholders[] = 'community';
        }
        if (str_contains($text, 'environment') || str_contains($text, 'nature') || str_contains($text, 'ecosystem')) {
            $stakeholders[] = 'environment';
        }
        return count($stakeholders) > 0 ? $stakeholders : ['affected parties'];
    }

    private function identifyEmotionalRisks(string $text): array
    {
        $risks = [];
        if (str_contains($text, 'change') || str_contains($text, 'transform')) {
            $risks[] = 'Resistance to change';
        }
        if (str_contains($text, 'reduce') || str_contains($text, 'cut') || str_contains($text, 'decrease')) {
            $risks[] = 'Anxiety about loss';
        }
        if (str_contains($text, 'increase') || str_contains($text, 'expand') || str_contains($text, 'grow')) {
            $risks[] = 'Fear of overwhelm';
        }
        return count($risks) > 0 ? $risks : ['Unknown emotional impact'];
    }

    private function generatePathosVerdict(string $proposition, string $primaryEmotion, float $intensity, array $stakeholders, ?PerspectiveOpinion $previous): string
    {
        $emotionalAppeal = $intensity > 0.6 ? 'strong' : 'moderate';
        $count = count($stakeholders);

        if ($previous) {
            return "After reconsideration, the emotional impact on " . implode(', ', $stakeholders) . " remains a key factor. The proposition has {$emotionalAppeal} emotional appeal with {$primaryEmotion} as the primary emotion.";
        }

        return "From an emotional standpoint, this proposition affects {$count} stakeholder groups: " . implode(', ', $stakeholders) . ". The primary emotion of {$primaryEmotion} with {$emotionalAppeal} intensity suggests " . ($intensity > 0.5 ? 'proceeding with empathetic consideration' : 'the emotional impact may be manageable') . ".";
    }

    private function calculatePathosConfidence(string $primaryEmotion, float $intensity, array $stakeholders, array $previousOpinions): float
    {
        $confidence = 0.5;
        $confidence += min(0.2, count($stakeholders) * 0.05);
        if ($primaryEmotion !== 'neutral') {
            $confidence += 0.1;
        }
        $previousLogos = $this->findOpinion($previousOpinions, PerspectiveType::LOGOS);
        if ($previousLogos && $previousLogos->confidence > 0.6) {
            $confidence += 0.05;
        }
        return min(1.0, $confidence);
    }

    private function generatePathosArguments(string $primaryEmotion, float $intensity, array $stakeholders, array $risks): array
    {
        $args = ["Addresses emotional needs of " . implode(' and ', $stakeholders)];
        if ($intensity > 0.6) {
            $args[] = "Strong emotional engagement through {$primaryEmotion}";
        }
        if (count($risks) > 0) {
            $args[] = "Requires managing: " . implode(', ', $risks);
        }
        return $args;
    }

    private function identifyPathosConcerns(float $intensity, int $stakeholderCount, array $risks): array
    {
        $concerns = [];
        if ($intensity > 0.7) {
            $concerns[] = 'High emotional intensity may cloud rational judgment';
        }
        if ($stakeholderCount > 3) {
            $concerns[] = 'Multiple stakeholder groups may have conflicting emotional needs';
        }
        foreach ($risks as $risk) {
            if (str_contains($risk, 'Resistance') || str_contains($risk, 'Anxiety')) {
                $concerns[] = 'Potential for emotional resistance that needs addressing';
            }
        }
        return count($concerns) > 0 ? $concerns : ['Emotional impact appears manageable'];
    }

    private function generatePathosSuggestions(string $primaryEmotion, array $stakeholders): array
    {
        $suggestions = ["Consider communication strategy for " . implode(' and ', $stakeholders)];
        if ($primaryEmotion !== 'neutral') {
            $suggestions[] = "Address the {$primaryEmotion} emotion directly in implementation";
        }
        return $suggestions;
    }

    // ===== Logos helpers =====

    private function extractPremises(string $text): array
    {
        $premises = [];
        if (str_contains($text, 'because') || str_contains($text, 'since')) {
            $premises[] = 'Causal reasoning detected in argument';
        }
        if (str_contains($text, 'data') || str_contains($text, 'evidence') || str_contains($text, 'research')) {
            $premises[] = 'Empirical evidence cited';
        }
        if (str_contains($text, 'experience') || str_contains($text, 'history') || str_contains($text, 'track record')) {
            $premises[] = 'Historical precedent referenced';
        }
        return count($premises) > 0 ? $premises : ['Implicit premises require examination'];
    }

    private function identifyAssumptions(string $text): array
    {
        $assumptions = [];
        if (str_contains($text, 'will') || str_contains($text, 'going to')) {
            $assumptions[] = 'Future prediction assumed certain';
        }
        if (str_contains($text, 'always') || str_contains($text, 'never')) {
            $assumptions[] = 'Absolute statements may oversimplify';
        }
        if (str_contains($text, 'everyone') || str_contains($text, 'nobody')) {
            $assumptions[] = 'Universal claims need verification';
        }
        return count($assumptions) > 0 ? $assumptions : ['Standard assumptions apply'];
    }

    private function identifyLogicalRisks(string $text): array
    {
        $risks = [];
        if (str_contains($text, 'all') && str_contains($text, 'must')) {
            $risks[] = 'Potential hasty generalization';
        }
        if (str_contains($text, 'either') && str_contains($text, 'or')) {
            $risks[] = 'Possible false dichotomy';
        }
        return count($risks) > 0 ? $risks : ['Logical structure appears sound'];
    }

    private function generateLogosVerdict(string $structure, int $premiseCount, array $assumptions, array $risks, ?PerspectiveOpinion $previous): string
    {
        $structures = [
            'deductive' => 'following a deductive structure from general principles',
            'inductive' => 'building from specific observations to general conclusions',
            'abductive' => 'inferring the most likely explanation',
            'analogical' => 'drawing parallels from comparable situations',
        ];

        if ($previous) {
            return "Upon re-examination, the {$structure} reasoning remains valid with {$premiseCount} supporting premises.";
        }

        return "The proposition presents {$structures[$structure]} with {$premiseCount} premises identified. The argument's logical foundation is " . (count($assumptions) > 1 ? 'subject to several assumptions that warrant scrutiny' : 'relatively straightforward') . ".";
    }

    private function calculateLogosConfidence(int $premiseCount, int $assumptionCount, int $riskCount, array $previousOpinions): float
    {
        $confidence = 0.5;
        $confidence += min(0.2, $premiseCount * 0.05);
        $confidence -= min(0.15, $assumptionCount * 0.03);
        $confidence -= min(0.1, $riskCount * 0.02);
        $previousEthos = $this->findOpinion($previousOpinions, PerspectiveType::ETHOS);
        if ($previousEthos && $previousEthos->confidence > 0.6) {
            $confidence += 0.05;
        }
        return min(1.0, max(0.0, $confidence));
    }

    private function generateLogosArguments(string $structure, array $premises, array $assumptions): array
    {
        $args = ["Reasoning follows {$structure} structure"];
        foreach (array_slice($premises, 0, 3) as $premise) {
            $args[] = "Premise: {$premise}";
        }
        if (count($assumptions) > 0) {
            $args[] = "Assumptions to verify: " . implode(', ', $assumptions);
        }
        return $args;
    }

    private function identifyLogosConcerns(int $premiseCount, int $assumptionCount, array $risks): array
    {
        $concerns = [];
        if ($assumptionCount > 2) {
            $concerns[] = 'Multiple unverified assumptions in the argument';
        }
        foreach ($risks as $risk) {
            if (!str_contains($risk, 'appears sound')) {
                $concerns[] = $risk;
            }
        }
        if (count($concerns) === 0) {
            return ['Logical structure is sound'];
        }
        return $concerns;
    }

    private function assessLogicalValidity(int $riskCount, int $assumptionCount): float
    {
        $validity = 0.7;
        $validity -= min(0.2, $riskCount * 0.05);
        $validity -= min(0.1, $assumptionCount * 0.02);
        return max(0.3, $validity);
    }

    private function generateLogosSuggestions(array $assumptions, int $premiseCount): array
    {
        $suggestions = [];
        foreach (array_slice($assumptions, 0, 2) as $assumption) {
            $suggestions[] = "Verify assumption: {$assumption}";
        }
        if ($premiseCount === 0) {
            $suggestions[] = 'Make implicit premises explicit for clarity';
        }
        return $suggestions;
    }

    // ===== Ethos helpers =====

    private function identifyPrinciples(string $text): array
    {
        $principles = [];
        if (str_contains($text, 'fair') || str_contains($text, 'equitable') || str_contains($text, 'just')) {
            $principles[] = 'Fairness and justice';
        }
        if (str_contains($text, 'honest') || str_contains($text, 'transparent') || str_contains($text, 'truthful')) {
            $principles[] = 'Honesty and transparency';
        }
        if (str_contains($text, 'respect') || str_contains($text, 'dignity') || str_contains($text, 'worth')) {
            $principles[] = 'Respect for dignity';
        }
        if (str_contains($text, 'responsibility') || str_contains($text, 'accountable') || str_contains($text, 'liable')) {
            $principles[] = 'Accountability and responsibility';
        }
        return count($principles) > 0 ? $principles : ['General ethical principles apply'];
    }

    private function identifyValuesAtStake(string $text): array
    {
        $values = [];
        if (str_contains($text, 'privacy') || str_contains($text, 'confidential') || str_contains($text, 'secret')) {
            $values[] = 'Privacy rights';
        }
        if (str_contains($text, 'safety') || str_contains($text, 'security') || str_contains($text, 'protection')) {
            $values[] = 'Safety and security';
        }
        if (str_contains($text, 'freedom') || str_contains($text, 'autonomy') || str_contains($text, 'choice')) {
            $values[] = 'Autonomy and freedom';
        }
        if (str_contains($text, 'equality') || str_contains($text, 'equal') || str_contains($text, 'discriminat')) {
            $values[] = 'Equality and non-discrimination';
        }
        return count($values) > 0 ? $values : ['Standard ethical values'];
    }

    private function identifyEthicalRisks(string $text): array
    {
        $risks = [];
        if (str_contains($text, 'profit') && !str_contains($text, 'people')) {
            $risks[] = 'Potential prioritization of profit over people';
        }
        if (str_contains($text, 'secret') || str_contains($text, 'hidden') || str_contains($text, 'undisclosed')) {
            $risks[] = 'Transparency concerns';
        }
        if (str_contains($text, 'minority') || str_contains($text, 'vulnerable') || str_contains($text, 'disadvantaged')) {
            $risks[] = 'Impact on vulnerable populations needs assessment';
        }
        return count($risks) > 0 ? $risks : ['No significant ethical risks identified'];
    }

    private function generateEthosVerdict(string $framework, array $principles, array $values, array $risks, ?PerspectiveOpinion $previous): string
    {
        if ($previous) {
            return "Upon ethical re-evaluation using {$framework} framework, the proposition's alignment with " . implode(', ', $principles) . " " . ($this->hasSignificantRisks($risks) ? 'requires attention to identified risks' : 'remains acceptable') . ".";
        }

        return "From an ethical perspective informed by {$framework} principles, this proposition " . (count($values) > 2 ? 'implicates multiple values' : 'has limited ethical implications') . ". The key principles at stake are " . implode(' and ', array_slice($principles, 0, 2)) . ".";
    }

    private function calculateEthosConfidence(int $principleCount, int $riskCount, array $previousOpinions): float
    {
        $confidence = 0.6;
        $confidence += min(0.1, $principleCount * 0.02);
        if ($riskCount === 0) {
            $confidence += 0.1;
        } else {
            $confidence -= min(0.15, $riskCount * 0.03);
        }
        $previousPathos = $this->findOpinion($previousOpinions, PerspectiveType::PATHOS);
        if ($previousPathos && $previousPathos->confidence > 0.6) {
            $confidence += 0.05;
        }
        return min(1.0, max(0.0, $confidence));
    }

    private function generateEthosArguments(string $framework, array $principles, array $values): array
    {
        $args = ["Ethical framework: {$framework}"];
        foreach (array_slice($principles, 0, 2) as $principle) {
            $args[] = "Principle invoked: {$principle}";
        }
        if (count($values) > 0) {
            $args[] = "Values at stake: " . implode(', ', $values);
        }
        return $args;
    }

    private function identifyEthosConcerns(array $risks, int $valueCount): array
    {
        $concerns = [];
        foreach ($risks as $risk) {
            if (!str_contains($risk, 'No significant')) {
                $concerns[] = $risk;
            }
        }
        if ($valueCount > 3) {
            $concerns[] = 'Multiple values may create ethical tensions';
        }
        return count($concerns) > 0 ? $concerns : ['Ethical considerations appear manageable'];
    }

    private function assessEthicalAlignment(int $riskCount): float
    {
        $alignment = 0.7;
        $alignment -= min(0.2, $riskCount * 0.05);
        return max(0.3, $alignment);
    }

    private function generateEthosSuggestions(array $risks, string $framework, int $valueCount): array
    {
        $suggestions = [];
        if ($this->hasSignificantRisks($risks)) {
            $suggestions[] = 'Address identified ethical risks before implementation';
        }
        if ($valueCount > 2) {
            $suggestions[] = 'Consider ethical trade-offs between competing values';
        }
        $suggestions[] = "Apply {$framework} framework consistently";
        return $suggestions;
    }

    // ===== Shared helpers =====

    private function findOpinion(array $opinions, PerspectiveType $type): ?PerspectiveOpinion
    {
        foreach ($opinions as $op) {
            if ($op->perspective === $type) return $op;
        }
        return null;
    }

    private function hasSignificantRisks(array $risks): bool
    {
        foreach ($risks as $risk) {
            if (!str_contains($risk, 'No significant')) {
                return true;
            }
        }
        return false;
    }
}