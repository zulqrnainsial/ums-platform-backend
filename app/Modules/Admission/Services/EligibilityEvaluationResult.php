<?php

namespace App\Modules\Admission\Services;

class EligibilityEvaluationResult
{
    public function __construct(
        public bool $eligible = true,
        public array $passedRules = [],
        public array $failedRules = [],
        public array $warningRules = [],
    ) {
    }

    public function addPassed(array $rule): void
    {
        $this->passedRules[] = $rule;
    }

    public function addFailed(array $rule): void
    {
        $this->eligible = false;
        $this->failedRules[] = $rule;
    }

    public function addWarning(array $rule): void
    {
        $this->warningRules[] = $rule;
    }

    public function toArray(): array
    {
        return [
            'eligible' => $this->eligible,
            'passed_rules' => $this->passedRules,
            'failed_rules' => $this->failedRules,
            'warning_rules' => $this->warningRules,
        ];
    }
}