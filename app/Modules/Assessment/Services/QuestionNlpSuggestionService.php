<?php

namespace App\Modules\Assessment\Services;

class QuestionNlpSuggestionService
{
    public function __construct(
    private readonly QuestionAiBloomClassifierService $aiClassifier
    ) {
    }
    public function suggest(array $payload): array
    {
        $rawText = (string) ($payload['question_text'] ?? $payload['question_html'] ?? '');

        $questionText = $this->cleanText($rawText);
        $normalizedText = $this->normalizeText($questionText);
$aiResult = $this->aiClassifier->classify($questionText);

if ($aiResult) {
    return [
        'question_text' => $questionText,

        'detected_action_verbs' => [],
        'detected_phrases' => [],
        'bloom_scores' => $aiResult['scores'] ?? [],

        'suggestions' => [
            'bloom_level_code' => $aiResult['bloom_level_code'],
            'difficulty_code' => $aiResult['difficulty_code'],
            'cognitive_level_code' => $aiResult['cognitive_level_code'],
        ],

        'confidence' => $aiResult['confidence'] ?? 'medium',
        'reason' => $aiResult['reason'] ?? 'Suggested by AI/ML Bloom classifier.',
        'rule_engine_version' => $aiResult['model_version'] ?? 'local_ml_classifier_v1',

        'provider' => $aiResult['provider'] ?? 'local_ml_classifier',
        'fallback_used' => false,
    ];
}
        $tokens = $this->tokens($normalizedText);
        $phrases = $this->detectedPhrases($normalizedText);
        $verbs = $this->detectedActionVerbs($normalizedText, $tokens);

        $scores = $this->scoreBloomLevels($normalizedText, $tokens, $phrases, $verbs);

        $bloomLevel = $this->highestScoringBloomLevel($scores);
        $difficulty = $this->suggestDifficulty($bloomLevel, $normalizedText, $tokens, $phrases);
        $cognitiveLevel = $this->suggestCognitiveLevel($bloomLevel);

        return [
            'question_text' => $questionText,

            'detected_action_verbs' => $verbs,
            'detected_phrases' => $phrases,
            'bloom_scores' => $scores,
            'provider' => 'semantic_rule_engine',
            'fallback_used' => true,
            'suggestions' => [
                'bloom_level_code' => $bloomLevel,
                'difficulty_code' => $difficulty,
                'cognitive_level_code' => $cognitiveLevel,
            ],

            'confidence' => $this->confidence($scores, $phrases, $verbs),
            'reason' => $this->reason($bloomLevel, $difficulty, $scores, $phrases, $verbs),

            'rule_engine_version' => 'semantic_rule_based_v2',
        ];
    }

    private function cleanText(string $text): string
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim((string) $text);
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower($text);
        $text = str_replace(['?', '.', ',', ';', ':', '!', '(', ')', '[', ']', '{', '}'], ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim((string) $text);
    }

    private function tokens(string $text): array
    {
        $parts = preg_split('/\s+/u', $text);

        return array_values(array_filter(array_map('trim', $parts ?: [])));
    }

    private function detectedActionVerbs(string $text, array $tokens): array
    {
        $detected = [];

        foreach ($this->verbLexicon() as $level => $verbs) {
            foreach ($verbs as $verb) {
                if ($this->containsWord($tokens, $verb)) {
                    $detected[] = [
                        'verb' => $verb,
                        'bloom_level_code' => $level,
                        'match_type' => 'verb',
                    ];
                }
            }
        }

        return $this->uniqueDetectedItems($detected, 'verb');
    }

    private function detectedPhrases(string $text): array
    {
        $detected = [];

        foreach ($this->phraseLexicon() as $level => $phrases) {
            foreach ($phrases as $phrase => $weight) {
                if ($this->containsPhrase($text, $phrase)) {
                    $detected[] = [
                        'phrase' => $phrase,
                        'bloom_level_code' => $level,
                        'weight' => $weight,
                        'match_type' => 'phrase',
                    ];
                }
            }
        }

        return $this->uniqueDetectedItems($detected, 'phrase');
    }

    private function scoreBloomLevels(
        string $text,
        array $tokens,
        array $phrases,
        array $verbs
    ): array {
        $scores = [
            'remember' => 0,
            'understand' => 0,
            'apply' => 0,
            'analyze' => 0,
            'evaluate' => 0,
            'create' => 0,
        ];

        foreach ($verbs as $item) {
            $level = $item['bloom_level_code'];
            $scores[$level] += $this->verbWeight($level, $item['verb']);
        }

        foreach ($phrases as $item) {
            $level = $item['bloom_level_code'];
            $scores[$level] += (float) $item['weight'];
        }

        $this->applyQuestionPatternScores($scores, $text, $tokens);
        $this->applyOopContextScores($scores, $text, $tokens);

        return array_map(
            fn ($score) => round((float) $score, 2),
            $scores
        );
    }

    private function applyQuestionPatternScores(array &$scores, string $text, array $tokens): void
    {
        if ($this->containsPhrase($text, 'why')) {
            $scores['understand'] += 1.5;
            $scores['evaluate'] += 1.0;
        }

        if ($this->containsPhrase($text, 'why do you think')) {
            $scores['evaluate'] += 3.0;
        }

        if ($this->containsPhrase($text, 'give reasons')) {
            $scores['evaluate'] += 3.5;
        }

        if ($this->containsPhrase($text, 'give reason')) {
            $scores['evaluate'] += 3.5;
        }

        if ($this->containsPhrase($text, 'with reasons')) {
            $scores['evaluate'] += 3.0;
        }

        if ($this->containsPhrase($text, 'support your answer')) {
            $scores['evaluate'] += 3.0;
        }

        if ($this->containsPhrase($text, 'do you agree')) {
            $scores['evaluate'] += 3.5;
        }

        if ($this->containsPhrase($text, 'which is better')) {
            $scores['evaluate'] += 3.0;
        }

        if ($this->containsPhrase($text, 'when should')) {
            $scores['evaluate'] += 2.0;
            $scores['analyze'] += 1.0;
        }

        if ($this->containsPhrase($text, 'difference between')) {
            $scores['analyze'] += 3.0;
        }

        if ($this->containsPhrase($text, 'compare and contrast')) {
            $scores['analyze'] += 3.5;
        }

        if ($this->containsPhrase($text, 'write a program')) {
            $scores['apply'] += 4.0;
        }

        if ($this->containsPhrase($text, 'write code')) {
            $scores['apply'] += 4.0;
        }

        if ($this->containsPhrase($text, 'create a class')) {
            $scores['apply'] += 3.5;
            $scores['create'] -= 1.0;
        }

        if ($this->containsPhrase($text, 'design a system')) {
            $scores['create'] += 4.0;
        }

        if ($this->containsPhrase($text, 'design an architecture')) {
            $scores['create'] += 4.0;
        }

        if ($this->containsPhrase($text, 'propose a design')) {
            $scores['create'] += 4.0;
        }

        if ($this->containsPhrase($text, 'real life example')) {
            $scores['understand'] += 2.0;
        }

        if ($this->containsPhrase($text, 'with example')) {
            $scores['understand'] += 1.5;
        }

        if ($this->containsPhrase($text, 'using example')) {
            $scores['understand'] += 1.5;
        }
    }

    private function applyOopContextScores(array &$scores, string $text, array $tokens): void
    {
        $hasCodeIntent = $this->containsAnyPhrase($text, [
            'write code',
            'write a program',
            'implement',
            'create a class',
            'define a class',
            'use inheritance',
            'use polymorphism',
            'override method',
            'overload method',
        ]);

        if ($hasCodeIntent) {
            $scores['apply'] += 2.5;
        }

        $hasDesignIntent = $this->containsAnyPhrase($text, [
            'design class diagram',
            'design a class diagram',
            'design an oop model',
            'design an oop based',
            'propose classes',
            'identify classes and relationships',
            'library management system',
            'university management system',
            'shopping cart system',
        ]);

        if ($hasDesignIntent) {
            $scores['create'] += 3.5;
        }

        $hasDesignJudgmentIntent = $this->containsAnyPhrase($text, [
            'is inheritance appropriate',
            'should inheritance be used',
            'when to use inheritance',
            'when should inheritance',
            'prefer composition',
            'composition over inheritance',
            'best design choice',
        ]);

        if ($hasDesignJudgmentIntent) {
            $scores['evaluate'] += 3.5;
            $scores['analyze'] += 1.5;
        }

        $hasComparisonIntent = $this->containsAnyPhrase($text, [
            'inheritance and composition',
            'abstract class and interface',
            'overloading and overriding',
            'compile time and runtime polymorphism',
            'class and object',
        ]);

        if ($hasComparisonIntent && $this->containsAnyPhrase($text, ['compare', 'differentiate', 'difference between', 'distinguish'])) {
            $scores['analyze'] += 3.0;
        }
    }

    private function highestScoringBloomLevel(array $scores): string
    {
        $priority = [
            'create' => 6,
            'evaluate' => 5,
            'analyze' => 4,
            'apply' => 3,
            'understand' => 2,
            'remember' => 1,
        ];

        $bestLevel = 'remember';
        $bestScore = -999;

        foreach ($scores as $level => $score) {
            if ($score > $bestScore) {
                $bestLevel = $level;
                $bestScore = $score;
                continue;
            }

            if ($score === $bestScore && ($priority[$level] ?? 0) > ($priority[$bestLevel] ?? 0)) {
                $bestLevel = $level;
            }
        }

        if ($bestScore <= 0) {
            return 'remember';
        }

        return $bestLevel;
    }

    private function suggestDifficulty(string $bloomLevel, string $text, array $tokens, array $phrases): string
    {
        $wordCount = count($tokens);

        $hasCode = $this->containsAnyPhrase($text, [
            'write code',
            'write a program',
            'implement',
            'create a class',
            'method overriding',
            'method overloading',
        ]);

        $hasDesign = $this->containsAnyPhrase($text, [
            'design',
            'architecture',
            'class diagram',
            'system',
            'model',
        ]);

        $hasJudgment = $this->containsAnyPhrase($text, [
            'justify',
            'give reasons',
            'support your answer',
            'do you agree',
            'evaluate',
            'critique',
            'best',
            'appropriate',
        ]);

        if (in_array($bloomLevel, ['create', 'evaluate', 'analyze'], true)) {
            return 'hard';
        }

        if ($bloomLevel === 'apply' && ($hasCode || $wordCount > 18)) {
            return 'medium';
        }

        if ($bloomLevel === 'understand' && ($wordCount > 25 || $hasJudgment || $hasDesign)) {
            return 'medium';
        }

        return $bloomLevel === 'remember' ? 'easy' : 'medium';
    }

    private function suggestCognitiveLevel(string $bloomLevel): string
    {
        return match ($bloomLevel) {
            'remember' => 'remembering',
            'understand' => 'understanding',
            'apply' => 'applying',
            'analyze' => 'analyzing',
            'evaluate' => 'evaluating',
            'create' => 'creating',
            default => 'understanding',
        };
    }

    private function confidence(array $scores, array $phrases, array $verbs): string
    {
        rsort($scores);

        $top = $scores[0] ?? 0;
        $second = $scores[1] ?? 0;
        $gap = $top - $second;

        if ($top >= 5 && $gap >= 2 && (count($phrases) > 0 || count($verbs) > 0)) {
            return 'high';
        }

        if ($top >= 3 && $gap >= 1) {
            return 'medium';
        }

        return 'low';
    }

    private function reason(
        string $bloomLevel,
        string $difficulty,
        array $scores,
        array $phrases,
        array $verbs
    ): string {
        $matchedPhrases = array_map(
            fn ($item) => $item['phrase'],
            $phrases
        );

        $matchedVerbs = array_map(
            fn ($item) => $item['verb'],
            $verbs
        );

        $evidence = [];

        if (count($matchedPhrases) > 0) {
            $evidence[] = 'Detected phrase(s): ' . implode(', ', array_unique($matchedPhrases));
        }

        if (count($matchedVerbs) > 0) {
            $evidence[] = 'Detected action verb(s): ' . implode(', ', array_unique($matchedVerbs));
        }

        if (count($evidence) === 0) {
            $evidence[] = 'No strong Bloom phrase was detected; suggestion is based on general question structure.';
        }

        $scoreText = collect($scores)
            ->map(fn ($score, $level) => "{$level}: {$score}")
            ->implode(', ');

        return implode('. ', $evidence)
            . ". Suggested Bloom level: {$bloomLevel}. Suggested difficulty: {$difficulty}. Scores: {$scoreText}.";
    }

    private function verbWeight(string $level, string $verb): float
    {
        return match ($level) {
            'remember' => 1.5,
            'understand' => 2.0,
            'apply' => 2.5,
            'analyze' => 3.0,
            'evaluate' => 3.5,
            'create' => 4.0,
            default => 1.0,
        };
    }

    private function containsWord(array $tokens, string $word): bool
    {
        return in_array($word, $tokens, true);
    }

    private function containsPhrase(string $text, string $phrase): bool
    {
        $pattern = '/\b' . preg_quote($phrase, '/') . '\b/u';

        return preg_match($pattern, $text) === 1;
    }

    private function containsAnyPhrase(string $text, array $phrases): bool
    {
        foreach ($phrases as $phrase) {
            if ($this->containsPhrase($text, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function uniqueDetectedItems(array $items, string $key): array
    {
        $seen = [];
        $unique = [];

        foreach ($items as $item) {
            $value = $item[$key] ?? null;

            if (!$value || isset($seen[$value])) {
                continue;
            }

            $seen[$value] = true;
            $unique[] = $item;
        }

        return $unique;
    }

    private function verbLexicon(): array
    {
        return [
            'remember' => [
                'define',
                'list',
                'identify',
                'name',
                'state',
                'recall',
                'recognize',
                'select',
                'label',
                'match',
                'write',
                'mention',
                'tell',
            ],

            'understand' => [
                'explain',
                'describe',
                'summarize',
                'interpret',
                'classify',
                'discuss',
                'illustrate',
                'paraphrase',
                'outline',
                'clarify',
                'show',
            ],

            'apply' => [
                'apply',
                'solve',
                'calculate',
                'use',
                'implement',
                'demonstrate',
                'execute',
                'compute',
                'perform',
                'run',
                'code',
                'program',
            ],

            'analyze' => [
                'analyze',
                'differentiate',
                'distinguish',
                'compare',
                'contrast',
                'examine',
                'investigate',
                'categorize',
                'organize',
                'infer',
                'separate',
                'breakdown',
            ],

            'evaluate' => [
                'evaluate',
                'justify',
                'judge',
                'critique',
                'assess',
                'defend',
                'argue',
                'recommend',
                'validate',
                'prioritize',
                'reason',
                'decide',
                'support',
            ],

            'create' => [
                'create',
                'design',
                'develop',
                'formulate',
                'construct',
                'compose',
                'propose',
                'plan',
                'generate',
                'build',
                'model',
            ],
        ];
    }

    private function phraseLexicon(): array
    {
        return [
            'remember' => [
                'what is' => 1.5,
                'what are' => 1.5,
                'define the term' => 2.0,
                'list the' => 2.0,
                'name the' => 2.0,
                'state the' => 2.0,
                'identify the' => 2.0,
            ],

            'understand' => [
                'explain why' => 2.5,
                'explain how' => 2.5,
                'describe how' => 2.0,
                'describe why' => 2.0,
                'in your own words' => 2.5,
                'with example' => 2.0,
                'using example' => 2.0,
                'real life example' => 2.0,
                'explain the concept' => 2.0,
            ],

            'apply' => [
                'write a program' => 4.0,
                'write code' => 4.0,
                'create a class' => 3.5,
                'implement a class' => 3.5,
                'use inheritance' => 3.0,
                'use polymorphism' => 3.0,
                'solve the problem' => 3.0,
                'apply the concept' => 3.0,
            ],

            'analyze' => [
                'difference between' => 3.0,
                'compare and contrast' => 3.5,
                'compare the' => 3.0,
                'differentiate between' => 3.0,
                'analyze the code' => 3.5,
                'identify the relationship' => 2.5,
                'break down' => 3.0,
                'find the error' => 2.5,
            ],

            'evaluate' => [
                'give reasons' => 4.0,
                'give reason' => 4.0,
                'with reasons' => 3.5,
                'support your answer' => 4.0,
                'justify your answer' => 4.0,
                'justify the statement' => 4.0,
                'do you agree' => 4.0,
                'which is better' => 3.5,
                'is it appropriate' => 3.5,
                'should be used' => 3.5,
                'when should' => 3.0,
                'best design choice' => 4.0,
                'critically evaluate' => 4.5,
            ],

            'create' => [
                'design a system' => 4.5,
                'design an architecture' => 4.5,
                'design a class diagram' => 4.0,
                'propose a design' => 4.0,
                'develop a model' => 4.0,
                'construct a solution' => 4.0,
                'create an architecture' => 4.0,
                'build a system' => 4.0,
            ],
        ];
    }
}