<?php
declare(strict_types=1);

namespace SuperInstance\Equipment\ConsensusEngine;

/**
 * WeightProfile for a domain
 */
 readonly class WeightProfile
{
    public function __construct(
        public float        $pathosWeight,
        public float        $logosWeight,
        public float        $ethosWeight,
        public DomainType   $domain,
        public string       $description = '',
        public array        $adjustmentRules = [],
    ) {}
}

/**
 * WeightAdjustmentRule for dynamically adjusting weights based on content
 */
 readonly class WeightAdjustmentRule
{
    public function __construct(
        public string           $name,
        public string            $condition,
        public string            $perspective,
        public float             $adjustment,
        public int               $priority,
    ) {}
}

/**
 * DomainCharacteristics for a domain
 */
 readonly class DomainCharacteristics
{
    public function __construct(
        public float $emotionalImportance,
        public float $logicalImportance,
        public float $ethicalImportance,
        public float $uncertaintyLevel,
        public float $stakeholderComplexity,
    ) {}
}

/**
 * WeightCalculator - Domain-specific weight calculation for perspectives
 * 
 * Calculates appropriate weights for Pathos, Logos, and Ethos perspectives
 * based on the domain of the deliberation.
 *
 * @package SuperInstance\Equipment\ConsensusEngine
 */
class WeightCalculator
{
    /** @var array<DomainType, WeightProfile> */
    private array $profiles = [];

    public function __construct(
        private readonly ?WeightProfile $customWeights = null,
    ) {
        $this->initProfiles();
    }

    private function initProfiles(): void
    {
        $this->profiles = [
            DomainType::FACTUAL->value => new WeightProfile(
                pathosWeight: 0.15, logosWeight: 0.60, ethosWeight: 0.25,
                domain: DomainType::FACTUAL,
                description: 'Scientific and data-driven decisions prioritize logic over emotion',
                adjustmentRules: [
                    new WeightAdjustmentRule('data_present', 'Content contains statistical data or research findings', 'logos', 0.1, 10),
                    new WeightAdjustmentRule('human_subjects', 'Research involves human subjects', 'ethos', 0.1, 8),
                ],
            ),
            DomainType::EMOTIONAL->value => new WeightProfile(
                pathosWeight: 0.50, logosWeight: 0.20, ethosWeight: 0.30,
                domain: DomainType::EMOTIONAL,
                description: 'Human-centered decisions value emotional resonance highly',
                adjustmentRules: [
                    new WeightAdjustmentRule('personal_story', 'Content includes personal narratives or testimonials', 'pathos', 0.15, 10),
                    new WeightAdjustmentRule('group_dynamics', 'Decision affects group relationships', 'pathos', 0.1, 8),
                ],
            ),
            DomainType::SENSITIVE->value => new WeightProfile(
                pathosWeight: 0.30, logosWeight: 0.25, ethosWeight: 0.45,
                domain: DomainType::SENSITIVE,
                description: 'Ethically complex decisions prioritize moral considerations',
                adjustmentRules: [
                    new WeightAdjustmentRule('vulnerable_populations', 'Decision affects vulnerable populations', 'ethos', 0.15, 10),
                    new WeightAdjustmentRule('rights_implications', 'Decision has rights implications', 'ethos', 0.1, 9),
                ],
            ),
            DomainType::CREATIVE->value => new WeightProfile(
                pathosWeight: 0.40, logosWeight: 0.30, ethosWeight: 0.30,
                domain: DomainType::CREATIVE,
                description: 'Creative decisions balance all perspectives with slight emotional edge',
                adjustmentRules: [
                    new WeightAdjustmentRule('innovation_focus', 'Decision involves innovation or new approaches', 'logos', 0.05, 7),
                    new WeightAdjustmentRule('artistic_expression', 'Decision involves artistic or creative expression', 'pathos', 0.1, 8),
                ],
            ),
            DomainType::BALANCED->value => new WeightProfile(
                pathosWeight: 0.333, logosWeight: 0.334, ethosWeight: 0.333,
                domain: DomainType::BALANCED,
                description: 'Equal weighting for general-purpose deliberations',
                adjustmentRules: [],
            ),
            DomainType::TECHNICAL->value => new WeightProfile(
                pathosWeight: 0.10, logosWeight: 0.70, ethosWeight: 0.20,
                domain: DomainType::TECHNICAL,
                description: 'Engineering decisions strongly prioritize logical reasoning',
                adjustmentRules: [
                    new WeightAdjustmentRule('safety_critical', 'Decision involves safety-critical systems', 'logos', 0.05, 10),
                    new WeightAdjustmentRule('user_impact', 'Technical change affects end users', 'pathos', 0.1, 7),
                ],
            ),
            DomainType::SOCIAL->value => new WeightProfile(
                pathosWeight: 0.40, logosWeight: 0.25, ethosWeight: 0.35,
                domain: DomainType::SOCIAL,
                description: 'Community decisions value emotional and ethical dimensions',
                adjustmentRules: [
                    new WeightAdjustmentRule('community_impact', 'Decision affects community cohesion', 'ethos', 0.1, 9),
                    new WeightAdjustmentRule('public_opinion', 'Decision will be subject to public scrutiny', 'pathos', 0.05, 8),
                ],
            ),
            DomainType::BUSINESS->value => new WeightProfile(
                pathosWeight: 0.25, logosWeight: 0.45, ethosWeight: 0.30,
                domain: DomainType::BUSINESS,
                description: 'Business decisions balance logic with stakeholder impact',
                adjustmentRules: [
                    new WeightAdjustmentRule('profit_pressure', 'Financial metrics are primary consideration', 'logos', 0.1, 8),
                    new WeightAdjustmentRule('reputation_risk', 'Decision affects company reputation', 'ethos', 0.1, 9),
                ],
            ),
            DomainType::MARITIME->value => new WeightProfile(
                pathosWeight: 0.20, logosWeight: 0.55, ethosWeight: 0.25,
                domain: DomainType::MARITIME,
                description: 'Maritime operations prioritize safety, logistics, and crew welfare',
                adjustmentRules: [
                    new WeightAdjustmentRule('safety_critical', 'Decision involves crew safety or vessel integrity', 'ethos', 0.15, 10),
                    new WeightAdjustmentRule('weather_hazard', 'Weather or sea conditions are a factor', 'logos', 0.1, 9),
                    new WeightAdjustmentRule('crew_welfare', 'Decision affects crew rest, health, or morale', 'pathos', 0.1, 8),
                    new WeightAdjustmentRule('regulatory_compliance', 'Decision involves catch limits, seasons, or regulations', 'ethos', 0.1, 9),
                ],
            ),
            DomainType::PERSONAL->value => new WeightProfile(
                pathosWeight: 0.45, logosWeight: 0.30, ethosWeight: 0.25,
                domain: DomainType::PERSONAL,
                description: 'Personal decisions emphasize individual emotional needs',
                adjustmentRules: [
                    new WeightAdjustmentRule('life_changing', 'Decision has significant life impact', 'pathos', 0.1, 10),
                    new WeightAdjustmentRule('relationships', 'Decision affects personal relationships', 'ethos', 0.1, 9),
                ],
            ),
        ];
    }

    /**
     * Gets the weight profile for a specific domain
     */
    public function getProfile(DomainType $domain): WeightProfile
    {
        $key = $domain->value;
        if (!isset($this->profiles[$key])) {
            throw new \DomainException("Unknown domain: {$domain->value}");
        }

        $profile = $this->profiles[$key];

        if ($this->customWeights !== null) {
            $profile = new WeightProfile(
                pathosWeight: $this->customWeights->pathosWeight ?? $profile->pathosWeight,
                logosWeight: $this->customWeights->logosWeight ?? $profile->logosWeight,
                ethosWeight: $this->customWeights->ethosWeight ?? $profile->ethosWeight,
                domain: $profile->domain,
                description: $profile->description,
                adjustmentRules: $profile->adjustmentRules,
            );
        }

        return $profile;
    }

    /**
     * Gets the weight for a specific perspective
     */
    public function getWeight(PerspectiveType $perspective, WeightProfile $profile): float
    {
        return match ($perspective) {
            PerspectiveType::PATHOS => $profile->pathosWeight,
            PerspectiveType::LOGOS => $profile->logosWeight,
            PerspectiveType::ETHOS => $profile->ethosWeight,
        };
    }

    /**
     * Detects the most appropriate domain from content
     */
    public function detectDomain(string $content): DomainType
    {
        $lower = strtolower($content);
        $scores = [];

        $factualScore = $this->scoreDomain($lower, ['data'=>2,'research'=>2,'study'=>1,'analysis'=>1,'evidence'=>2,'scientific'=>2,'measurement'=>1,'statistic'=>2,'empirical'=>2,'hypothesis'=>1,'experiment'=>1,'result'=>1,'fact'=>1]);
        $scores[] = ['domain' => DomainType::FACTUAL, 'score' => $factualScore];

        $emotionalScore = $this->scoreDomain($lower, ['feel'=>2,'emotion'=>2,'relationship'=>1,'care'=>1,'love'=>2,'passion'=>2,'heart'=>2,'empathy'=>2,'connection'=>1,'support'=>1,'understand'=>1,'personal'=>1,'family'=>1,'friend'=>1]);
        $scores[] = ['domain' => DomainType::EMOTIONAL, 'score' => $emotionalScore];

        $sensitiveScore = $this->scoreDomain($lower, ['ethics'=>2,'moral'=>2,'right'=>1,'wrong'=>1,'justice'=>2,'fair'=>1,'equality'=>2,'rights'=>2,'dignity'=>2,'vulnerable'=>2,'discrimination'=>2,'privacy'=>1,'consent'=>2,'harm'=>2]);
        $scores[] = ['domain' => DomainType::SENSITIVE, 'score' => $sensitiveScore];

        $technicalScore = $this->scoreDomain($lower, ['technical'=>2,'engineering'=>2,'system'=>1,'implement'=>1,'architecture'=>2,'code'=>1,'algorithm'=>2,'optimize'=>1,'performance'=>1,'infrastructure'=>2,'specification'=>1,'integration'=>1,'deploy'=>1,'scalability'=>1]);
        $scores[] = ['domain' => DomainType::TECHNICAL, 'score' => $technicalScore];

        $maritimeScore = $this->scoreDomain($lower, ['vessel'=>2,'crew'=>2,'safety'=>2,'captain'=>2,'fleet'=>1,'navigation'=>2,'weather'=>1,'sea'=>1,'port'=>1,'harbor'=>1,'maritime'=>2,'fishing'=>1,'catch'=>1,'tide'=>1,'anchor'=>1,'deck'=>1,'haul'=>1,'sortie'=>2,'bearing'=>1,'course'=>1]);
        $scores[] = ['domain' => DomainType::MARITIME, 'score' => $maritimeScore];

        $businessScore = $this->scoreDomain($lower, ['business'=>2,'profit'=>2,'market'=>1,'revenue'=>2,'customer'=>1,'strategy'=>1,'competitive'=>1,'investment'=>1,'roi'=>2,'stakeholder'=>1,'growth'=>1,'enterprise'=>1,'commercial'=>2,'industry'=>1]);
        $scores[] = ['domain' => DomainType::BUSINESS, 'score' => $businessScore];

        $socialScore = $this->scoreDomain($lower, ['community'=>2,'society'=>2,'public'=>1,'social'=>2,'people'=>1,'collective'=>2,'together'=>1,'group'=>1,'shared'=>1,'common'=>1,'collaborative'=>1,'participate'=>1,'democratic'=>2,'citizens'=>2]);
        $scores[] = ['domain' => DomainType::SOCIAL, 'score' => $socialScore];

        usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);

        if (($scores[0]['score'] ?? 0) < 3) {
            return DomainType::BALANCED;
        }

        return $scores[0]['domain'];
    }

    private function scoreDomain(string $content, array $keywords): int
    {
        $score = 0;
        foreach ($keywords as $keyword => $weight) {
            if (str_contains($content, $keyword)) {
                $score += $weight;
            }
        }
        return $score;
    }

    /**
     * Calculates adjusted weights based on content analysis
     */
    public function calculateAdjustedWeights(DomainType $domain, string $content, ?WeightProfile $baseWeights = null): WeightProfile
    {
        $profile = $baseWeights ?? $this->getProfile($domain);
        $rules = $profile->adjustmentRules;

        usort($rules, fn($a, $b) => $b->priority <=> $a->priority);

        $pathosWeight = $profile->pathosWeight;
        $logosWeight = $profile->logosWeight;
        $ethosWeight = $profile->ethosWeight;

        foreach ($rules as $rule) {
            if ($this->matchesCondition($content, $rule->condition)) {
                match ($rule->perspective) {
                    'pathos' => $pathosWeight += $rule->adjustment,
                    'logos' => $logosWeight += $rule->adjustment,
                    'ethos' => $ethosWeight += $rule->adjustment,
                    default => null,
                };
            }
        }

        $total = $pathosWeight + $logosWeight + $ethosWeight;

        return new WeightProfile(
            pathosWeight: $pathosWeight / $total,
            logosWeight: $logosWeight / $total,
            ethosWeight: $ethosWeight / $total,
            domain: $domain,
            description: "Adjusted profile for {$domain->value} domain based on content analysis",
            adjustmentRules: $rules,
        );
    }

    private function matchesCondition(string $content, string $condition): bool
    {
        $lowerContent = strtolower($content);
        preg_match_all('/\b[a-z]+\b/', strtolower($condition), $matches);
        $keywords = $matches[0] ?? [];
        $matching = array_filter($keywords, fn($kw) => str_contains($lowerContent, $kw));
        return count($matching) >= ceil(count($keywords) * 0.3);
    }

    /**
     * Lists all available domains
     * @return DomainType[]
     */
    public function listDomains(): array
    {
        return [
            DomainType::FACTUAL, DomainType::EMOTIONAL, DomainType::SENSITIVE,
            DomainType::CREATIVE, DomainType::BALANCED, DomainType::TECHNICAL,
            DomainType::SOCIAL, DomainType::BUSINESS, DomainType::PERSONAL,
            DomainType::MARITIME,
        ];
    }

    /**
     * Gets domain characteristics
     */
    public function getDomainCharacteristics(DomainType $domain): DomainCharacteristics
    {
        $chars = [
            DomainType::FACTUAL->value    => [0.2, 0.8, 0.4, 0.3, 0.3],
            DomainType::EMOTIONAL->value => [0.8, 0.3, 0.5, 0.5, 0.7],
            DomainType::SENSITIVE->value  => [0.6, 0.4, 0.9, 0.4, 0.8],
            DomainType::CREATIVE->value   => [0.7, 0.5, 0.4, 0.6, 0.4],
            DomainType::BALANCED->value   => [0.5, 0.5, 0.5, 0.5, 0.5],
            DomainType::TECHNICAL->value  => [0.1, 0.9, 0.3, 0.2, 0.2],
            DomainType::SOCIAL->value     => [0.7, 0.4, 0.6, 0.5, 0.9],
            DomainType::BUSINESS->value   => [0.4, 0.7, 0.5, 0.4, 0.6],
            DomainType::MARITIME->value   => [0.3, 0.8, 0.6, 0.5, 0.4],
            DomainType::PERSONAL->value   => [0.8, 0.4, 0.4, 0.6, 0.3],
        ];

        $vals = $chars[$domain->value] ?? [0.5, 0.5, 0.5, 0.5, 0.5];

        return new DomainCharacteristics(
            emotionalImportance: $vals[0],
            logicalImportance: $vals[1],
            ethicalImportance: $vals[2],
            uncertaintyLevel: $vals[3],
            stakeholderComplexity: $vals[4],
        );
    }

    /**
     * Validates a weight profile
     */
    public function validateProfile(WeightProfile $profile): array
    {
        $errors = [];

        if ($profile->pathosWeight < 0 || $profile->pathosWeight > 1) {
            $errors[] = 'pathosWeight must be between 0 and 1';
        }
        if ($profile->logosWeight < 0 || $profile->logosWeight > 1) {
            $errors[] = 'logosWeight must be between 0 and 1';
        }
        if ($profile->ethosWeight < 0 || $profile->ethosWeight > 1) {
            $errors[] = 'ethosWeight must be between 0 and 1';
        }

        $sum = $profile->pathosWeight + $profile->logosWeight + $profile->ethosWeight;
        if (abs($sum - 1.0) > 0.001) {
            $errors[] = "Weights must sum to 1, got {$sum}";
        }

        return ['valid' => count($errors) === 0, 'errors' => $errors];
    }
}