<?php

namespace App\Modules\Admission\Services;

use App\Modules\Admission\Models\AdmissionApplicantMeritScore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdmissionApplicantMeritScoreAdminService
{
    public function list(array $filters): array
    {
        $tenantId = $this->tenantId();

        $query = AdmissionApplicantMeritScore::query()
            ->from('admission_applicant_merit_scores as ms')
            ->leftJoin('applicants as app', 'app.id', '=', 'ms.applicant_id')
            ->leftJoin('admission_merit_formulas as mf', 'mf.id', '=', 'ms.admission_merit_formula_id')
            ->leftJoin('admission_sessions as s', 's.id', '=', 'ms.admission_session_id')
            ->leftJoin('offered_programs as op', 'op.id', '=', 'ms.offered_program_id')
            ->where('ms.tenant_id', $tenantId);

        $select = [
            'ms.id',
            'ms.applicant_id',
            'ms.admission_application_id',
            'ms.admission_session_id',
            'ms.offered_program_id',
            'ms.admission_preference_group_id',
            'ms.program_quota_seat_id',
            'ms.admission_merit_formula_id',
            'ms.total_component_weight',
            'ms.total_weighted_score',
            'ms.bonus_score',
            'ms.deduction_score',
            'ms.final_merit_score',
            'ms.is_eligible_for_merit',
            'ms.status_code',
            'ms.calculated_at',
        ];

        if (Schema::hasColumn('applicants', 'applicant_no')) {
            $select[] = 'app.applicant_no';
        } else {
            $select[] = DB::raw("NULL as applicant_no");
        }

        if (Schema::hasColumn('applicants', 'full_name')) {
            $select[] = 'app.full_name as applicant_name';
        } else {
            $select[] = DB::raw("NULL as applicant_name");
        }

        if (Schema::hasColumn('applicants', 'cnic_bform')) {
            $select[] = 'app.cnic_bform';
        } else {
            $select[] = DB::raw("NULL as cnic_bform");
        }

        if (Schema::hasColumn('applicants', 'email')) {
            $select[] = 'app.email as applicant_email';
        } else {
            $select[] = DB::raw("NULL as applicant_email");
        }

        if (Schema::hasColumn('applicants', 'phone')) {
            $select[] = 'app.phone as applicant_phone';
        } else {
            $select[] = DB::raw("NULL as applicant_phone");
        }

        if (Schema::hasColumn('admission_merit_formulas', 'code')) {
            $select[] = 'mf.code as formula_code';
        } else {
            $select[] = DB::raw("NULL as formula_code");
        }

        if (Schema::hasColumn('admission_merit_formulas', 'title')) {
            $select[] = 'mf.title as formula_title';
        } elseif (Schema::hasColumn('admission_merit_formulas', 'name')) {
            $select[] = 'mf.name as formula_title';
        } else {
            $select[] = DB::raw("NULL as formula_title");
        }

        if (Schema::hasColumn('admission_sessions', 'code')) {
            $select[] = 's.code as admission_session_code';
        } else {
            $select[] = DB::raw("NULL as admission_session_code");
        }

        if (Schema::hasColumn('admission_sessions', 'title')) {
            $select[] = 's.title as admission_session_title';
        } elseif (Schema::hasColumn('admission_sessions', 'name')) {
            $select[] = 's.name as admission_session_title';
        } elseif (Schema::hasColumn('admission_sessions', 'session_name')) {
            $select[] = 's.session_name as admission_session_title';
        } else {
            $select[] = DB::raw("NULL as admission_session_title");
        }

        if (Schema::hasColumn('offered_programs', 'code')) {
            $select[] = 'op.code as offered_program_code';
        } else {
            $select[] = DB::raw("NULL as offered_program_code");
        }

        if (Schema::hasColumn('offered_programs', 'title')) {
            $select[] = 'op.title as offered_program_title';
        } elseif (Schema::hasColumn('offered_programs', 'name')) {
            $select[] = 'op.name as offered_program_title';
        } elseif (Schema::hasColumn('offered_programs', 'program_title')) {
            $select[] = 'op.program_title as offered_program_title';
        } else {
            $select[] = DB::raw("NULL as offered_program_title");
        }

        $query->select($select);

        if (!empty($filters['admission_merit_formula_id'])) {
            $query->where('ms.admission_merit_formula_id', $filters['admission_merit_formula_id']);
        }

        if (!empty($filters['admission_session_id'])) {
            $query->where('ms.admission_session_id', $filters['admission_session_id']);
        }

        if (!empty($filters['offered_program_id'])) {
            $query->where('ms.offered_program_id', $filters['offered_program_id']);
        }

        if (!empty($filters['admission_preference_group_id'])) {
            $query->where('ms.admission_preference_group_id', $filters['admission_preference_group_id']);
        }

        if (!empty($filters['program_quota_seat_id'])) {
            $query->where('ms.program_quota_seat_id', $filters['program_quota_seat_id']);
        }

        if (array_key_exists('is_eligible_for_merit', $filters)
            && $filters['is_eligible_for_merit'] !== null
            && $filters['is_eligible_for_merit'] !== '') {
            $query->where('ms.is_eligible_for_merit', filter_var($filters['is_eligible_for_merit'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['status_code'])) {
            $query->where('ms.status_code', $filters['status_code']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                if (Schema::hasColumn('applicants', 'applicant_no')) {
                    $q->orWhere('app.applicant_no', 'like', "%{$search}%");
                }

                if (Schema::hasColumn('applicants', 'full_name')) {
                    $q->orWhere('app.full_name', 'like', "%{$search}%");
                }

                if (Schema::hasColumn('applicants', 'cnic_bform')) {
                    $q->orWhere('app.cnic_bform', 'like', "%{$search}%");
                }

                if (Schema::hasColumn('admission_merit_formulas', 'code')) {
                    $q->orWhere('mf.code', 'like', "%{$search}%");
                }

                if (Schema::hasColumn('admission_merit_formulas', 'title')) {
                    $q->orWhere('mf.title', 'like', "%{$search}%");
                }
            });
        }

        return $query
            ->orderByDesc('ms.final_merit_score')
            ->orderBy('ms.id')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->toArray();
    }

    public function detail(int $scoreId): array
    {
        $tenantId = $this->tenantId();

        $score = AdmissionApplicantMeritScore::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $scoreId)
            ->with(['components', 'formula'])
            ->firstOrFail();

        $applicant = null;

        if (Schema::hasTable('applicants')) {
            $applicant = DB::table('applicants')
                ->where('tenant_id', $tenantId)
                ->where('id', $score->applicant_id)
                ->first();
        }

        $session = null;

        if ($score->admission_session_id && Schema::hasTable('admission_sessions')) {
            $session = DB::table('admission_sessions')
                ->where('id', $score->admission_session_id)
                ->first();
        }

        $offeredProgram = null;

        if ($score->offered_program_id && Schema::hasTable('offered_programs')) {
            $offeredProgram = DB::table('offered_programs')
                ->where('id', $score->offered_program_id)
                ->first();
        }

        return [
            'score' => $score,
            'applicant' => $applicant,
            'formula' => $score->formula,
            'components' => $score->components,
            'admission_session' => $session,
            'offered_program' => $offeredProgram,
        ];
    }

    private function tenantId(): int
    {
        $tenantId = auth()->user()?->tenant_id;

        if (!$tenantId) {
            abort(403, 'Tenant context is required.');
        }

        return (int) $tenantId;
    }
}