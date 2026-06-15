<?php

namespace App\Modules\Admission\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PublicAdmissionGuidanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        $tenantId = $tenant?->id;

        if (!$tenantId) {
            return response()->json([
                'data' => [
                    'tenant' => null,
                    'sessions' => [],
                    'offered_programs' => [],
                    'eligibility_rules' => [],
                    'merit_formulas' => [],
                    'assessments' => [],
                    'required_documents' => [],
                    'message' => 'Tenant could not be resolved. Please use a valid tenant portal link or tenant code.',
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'tenant' => $this->tenantPayload($tenant),
                'sessions' => $this->openAdmissionSessions($tenantId),
                'offered_programs' => $this->offeredPrograms($tenantId),
                'eligibility_rules' => $this->eligibilitySummary($tenantId),
                'merit_formulas' => $this->meritFormulaSummary($tenantId),
                'assessments' => $this->availableAssessments($tenantId),
                'required_documents' => $this->requiredDocuments($tenantId),
                'how_to_apply' => $this->howToApply(),
            ],
        ]);
    }

    private function resolveTenant(Request $request): ?object
    {
        if (!Schema::hasTable('tenants')) {
            return null;
        }

        $tenantCode = $request->header('X-Tenant-Code')
            ?: $request->query('tenant_code')
            ?: $request->query('tenant')
            ?: null;

        $host = $request->getHost();

        $query = DB::table('tenants');

        if ($tenantCode) {
    $tenant = DB::table('tenants')
        ->where(function ($q) use ($tenantCode) {
            foreach (['code', 'slug', 'tenant_code', 'subdomain'] as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $q->orWhere($column, $tenantCode);
                }
            }

            if (Schema::hasColumn('tenants', 'name')) {
                $q->orWhere('name', $tenantCode);
            }
        })
        ->first();

    if ($tenant) {
        return $tenant;
    }
}

        if ($host && !in_array($host, ['localhost', '127.0.0.1'], true)) {
            $domainQuery = DB::table('tenants');

            $domainQuery->where(function ($q) use ($host) {
                foreach (['domain', 'subdomain', 'portal_domain'] as $column) {
                    if (Schema::hasColumn('tenants', $column)) {
                        $q->orWhere($column, $host);
                    }
                }
            });

            $tenant = $domainQuery->first();

            if ($tenant) {
                return $tenant;
            }
        }

        if (Schema::hasColumn('tenants', 'is_default')) {
            $defaultTenant = DB::table('tenants')
                ->where('is_default', 1)
                ->first();

            if ($defaultTenant) {
                return $defaultTenant;
            }
        }

        return DB::table('tenants')->orderBy('id')->first();
    }

    private function tenantPayload(object $tenant): array
    {
        return [
            'id' => $tenant->id ?? null,
            'code' => $tenant->code ?? $tenant->slug ?? $tenant->tenant_code ?? null,
            'name' => $tenant->name ?? $tenant->title ?? 'Institution',
            'email' => $tenant->email ?? null,
            'phone' => $tenant->phone ?? null,
            'address' => $tenant->address ?? null,
            'logo' => $tenant->logo ?? $tenant->logo_url ?? null,
        ];
    }

    private function openAdmissionSessions(int $tenantId): array
    {
        if (!Schema::hasTable('admission_sessions')) {
            return [];
        }

        $select = [
            'id',
            $this->safeCol('admission_sessions', 'code', 'code'),
            $this->safeCol('admission_sessions', 'name', 'name'),
            $this->safeCol('admission_sessions', 'title', 'title'),
            $this->safeCol('admission_sessions', 'application_start_date', 'application_start_date'),
            $this->safeCol('admission_sessions', 'application_end_date', 'application_end_date'),
            $this->safeCol('admission_sessions', 'document_submission_deadline', 'document_submission_deadline'),
            $this->safeCol('admission_sessions', 'test_start_date', 'test_start_date'),
            $this->safeCol('admission_sessions', 'test_end_date', 'test_end_date'),
            $this->safeCol('admission_sessions', 'merit_list_start_date', 'merit_list_start_date'),
            $this->safeCol('admission_sessions', 'status_code', 'status_code'),
        ];

        return DB::table('admission_sessions')
            ->select($select)
            ->where('tenant_id', $tenantId)
            ->when(Schema::hasColumn('admission_sessions', 'status_code'), function ($q) {
                $q->whereIn('status_code', ['open', 'active', 'published']);
            })
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->toArray();
    }

    private function offeredPrograms(int $tenantId): array
    {
        if (!Schema::hasTable('offered_programs')) {
            return [];
        }

        $query = DB::table('offered_programs as op');

        if (Schema::hasTable('programs') && Schema::hasColumn('offered_programs', 'program_id')) {
            $query->leftJoin('programs as p', 'p.id', '=', 'op.program_id');
        }

        if (Schema::hasTable('admission_sessions') && Schema::hasColumn('offered_programs', 'admission_session_id')) {
            $query->leftJoin('admission_sessions as s', 's.id', '=', 'op.admission_session_id');
        }

        $query->where('op.tenant_id', $tenantId);

        if (Schema::hasColumn('offered_programs', 'status_code')) {
            $query->whereIn('op.status_code', ['open', 'active', 'published']);
        }

        return $query
            ->select([
                'op.id',
                $this->safeAlias('op', 'code', 'offered_program_code'),
                $this->safeAlias('op', 'title', 'offered_program_title'),
                $this->safeAlias('op', 'name', 'offered_program_name'),
                $this->safeAlias('op', 'shift', 'shift'),
                $this->safeAlias('op', 'application_fee', 'application_fee'),
                $this->safeAlias('op', 'admission_fee', 'admission_fee'),
                $this->safeAlias('op', 'status_code', 'status_code'),
                Schema::hasColumn('offered_programs', 'program_id')
                    ? DB::raw('op.program_id as program_id')
                    : DB::raw('NULL as program_id'),
                Schema::hasTable('programs') && Schema::hasColumn('programs', 'name')
                    ? DB::raw('p.name as program_name')
                    : DB::raw('NULL as program_name'),
                Schema::hasTable('admission_sessions') && Schema::hasColumn('admission_sessions', 'name')
                    ? DB::raw('s.name as admission_session_name')
                    : DB::raw('NULL as admission_session_name'),
            ])
            ->orderByDesc('op.id')
            ->limit(30)
            ->get()
            ->toArray();
    }

    private function eligibilitySummary(int $tenantId): array
    {
        if (!Schema::hasTable('program_eligibility_rules')) {
            return [];
        }

        $query = DB::table('program_eligibility_rules as r')
            ->where('r.tenant_id', $tenantId);

        return $query
            ->select([
                'r.id',
                $this->safeAlias('r', 'offered_program_id', 'offered_program_id'),
                $this->safeAlias('r', 'program_quota_seat_id', 'program_quota_seat_id'),
                $this->safeAlias('r', 'rule_code', 'rule_code'),
                $this->safeAlias('r', 'rule_group', 'rule_group'),
                $this->safeAlias('r', 'rule_title', 'rule_title'),
                $this->safeAlias('r', 'operator', 'operator'),
                $this->safeAlias('r', 'value_text', 'value_text'),
                $this->safeAlias('r', 'value_number', 'value_number'),
            ])
            ->orderBy('r.offered_program_id')
            ->orderBy('r.rule_group')
            ->limit(100)
            ->get()
            ->toArray();
    }

    private function meritFormulaSummary(int $tenantId): array
    {
        if (!Schema::hasTable('admission_merit_formulas')) {
            return [];
        }

        $query = DB::table('admission_merit_formulas as f')
            ->where('f.tenant_id', $tenantId);

        if (Schema::hasColumn('admission_merit_formulas', 'status_code')) {
            $query->whereIn('f.status_code', ['active', 'published']);
        }

        $formulas = $query
            ->select([
                'f.id',
                $this->safeAlias('f', 'code', 'code'),
                $this->safeAlias('f', 'title', 'title'),
                $this->safeAlias('f', 'name', 'name'),
                $this->safeAlias('f', 'total_weight', 'total_weight'),
                $this->safeAlias('f', 'status_code', 'status_code'),
            ])
            ->orderByDesc('f.id')
            ->limit(20)
            ->get();

        if (!Schema::hasTable('admission_merit_formula_components')) {
            return $formulas->map(fn ($f) => array_merge((array) $f, ['components' => []]))->toArray();
        }

        $formulaIds = $formulas->pluck('id')->all();

        $components = DB::table('admission_merit_formula_components as c')
            ->whereIn('c.admission_merit_formula_id', $formulaIds)
            ->select([
                'c.id',
                'c.admission_merit_formula_id',
                $this->safeAlias('c', 'code', 'code'),
                $this->safeAlias('c', 'title', 'title'),
                $this->safeAlias('c', 'component_type', 'component_type'),
                $this->safeAlias('c', 'source_type', 'source_type'),
                $this->safeAlias('c', 'source_key', 'source_key'),
                $this->safeAlias('c', 'weight', 'weight'),
                $this->safeAlias('c', 'normalize_to', 'normalize_to'),
                $this->safeAlias('c', 'display_order', 'display_order'),
            ])
            ->orderBy('display_order')
            ->get()
            ->groupBy('admission_merit_formula_id');

        return $formulas->map(function ($formula) use ($components) {
            $item = (array) $formula;
            $item['components'] = $components->get($formula->id, collect())->values()->toArray();

            return $item;
        })->toArray();
    }

    private function availableAssessments(int $tenantId): array
    {
        if (!Schema::hasTable('assessments')) {
            return [];
        }

        $query = DB::table('assessments')
            ->where('tenant_id', $tenantId);

        if (Schema::hasColumn('assessments', 'mode')) {
            $query->whereIn('mode', ['online', 'admission']);
        }

        if (Schema::hasColumn('assessments', 'status_code')) {
            $query->whereIn('status_code', ['active', 'published']);
        }

        return $query
            ->select([
                'id',
                $this->safeCol('assessments', 'code', 'code'),
                $this->safeCol('assessments', 'title', 'title'),
                $this->safeCol('assessments', 'total_marks', 'total_marks'),
                $this->safeCol('assessments', 'passing_marks', 'passing_marks'),
                $this->safeCol('assessments', 'duration_minutes', 'duration_minutes'),
                $this->safeCol('assessments', 'attempt_limit', 'attempt_limit'),
                $this->safeCol('assessments', 'status_code', 'status_code'),
            ])
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->toArray();
    }

    private function requiredDocuments(int $tenantId): array
    {
        $documents = [];

        if (Schema::hasTable('program_eligibility_rules')) {
            $documents = DB::table('program_eligibility_rules')
                ->where('tenant_id', $tenantId)
                ->where('rule_code', 'REQUIRED_DOCUMENTS')
                ->select([
                    'id',
                    $this->safeCol('program_eligibility_rules', 'offered_program_id', 'offered_program_id'),
                    $this->safeCol('program_eligibility_rules', 'value_text', 'value_text'),
                    $this->safeCol('program_eligibility_rules', 'rule_title', 'rule_title'),
                ])
                ->limit(50)
                ->get()
                ->toArray();
        }

        return $documents;
    }

    private function howToApply(): array
    {
        return [
            [
                'title' => 'Create applicant account',
                'description' => 'Register yourself using CNIC/B-Form, email and mobile number.',
            ],
            [
                'title' => 'Complete profile',
                'description' => 'Enter personal information, qualifications, test results and upload required documents.',
            ],
            [
                'title' => 'Check eligible programs',
                'description' => 'The system will evaluate your eligibility according to program and quota rules.',
            ],
            [
                'title' => 'Submit application',
                'description' => 'Select eligible programs and submit your application before the deadline.',
            ],
            [
                'title' => 'Track merit and offer',
                'description' => 'After merit list publication, accepted candidates can receive and respond to admission offers.',
            ],
            [
                'title' => 'Pay admission voucher',
                'description' => 'Submit payment proof and wait for payment verification.',
            ],
            [
                'title' => 'Admission confirmation',
                'description' => 'After verification, your admission will be confirmed and student profile will be created.',
            ],
        ];
    }

    private function safeCol(string $table, string $column, string $alias): mixed
    {
        return Schema::hasColumn($table, $column)
            ? DB::raw("`{$column}` as `{$alias}`")
            : DB::raw("NULL as `{$alias}`");
    }

    private function safeAlias(string $alias, string $column, string $as): mixed
    {
        return Schema::hasColumn($this->tableFromAlias($alias), $column)
            ? DB::raw("{$alias}.`{$column}` as `{$as}`")
            : DB::raw("NULL as `{$as}`");
    }

    private function tableFromAlias(string $alias): string
    {
        return match ($alias) {
            'op' => 'offered_programs',
            'p' => 'programs',
            's' => 'admission_sessions',
            'r' => 'program_eligibility_rules',
            'f' => 'admission_merit_formulas',
            'c' => 'admission_merit_formula_components',
            default => $alias,
        };
    }
}