<?php

namespace App\Modules\Assessment\Services;

use Illuminate\Support\Facades\Http;

class QuestionAiBloomClassifierService
{
    public function classify(string $questionText): ?array
    {
        $enabled = (bool) config('services.bloom_classifier.enabled', false);

        if (!$enabled) {
            return null;
        }

        $baseUrl = rtrim((string) config('services.bloom_classifier.url'), '/');

        if ($baseUrl === '') {
            return null;
        }

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->post($baseUrl . '/classify', [
                    'question_text' => $questionText,
                    'domain' => 'assessment_question',
                ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            if (!is_array($data)) {
                return null;
            }

            if (empty($data['bloom_level_code']) || empty($data['difficulty_code'])) {
                return null;
            }

            return [
                'provider' => 'local_ml_classifier',
                'bloom_level_code' => $data['bloom_level_code'],
                'difficulty_code' => $data['difficulty_code'],
                'cognitive_level_code' => $data['cognitive_level_code']
                    ?? $this->cognitiveLevelFromBloom($data['bloom_level_code']),

                'confidence' => $data['confidence'] ?? null,
                'scores' => $data['scores'] ?? [],
                'reason' => $data['reason'] ?? 'AI/ML classifier suggestion.',
                'model_version' => $data['model_version'] ?? 'local_bloom_classifier_v1',
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function cognitiveLevelFromBloom(string $bloomLevel): string
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
}