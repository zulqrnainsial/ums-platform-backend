<?php

namespace App\Core\Dynamic\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\AcademicTerm;
use App\Modules\Academic\Models\Building;
use App\Modules\Academic\Models\Campus;
use App\Modules\Academic\Models\Department;
use App\Modules\Academic\Models\Faculty;
use App\Modules\Academic\Models\Floor;
use App\Modules\Academic\Models\Institute;
use App\Modules\Academic\Models\Program;
use App\Modules\Academic\Models\ProgramLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Modules\Subject\Models\Curriculum;
use App\Modules\Subject\Models\Subject;
use App\Modules\Subject\Models\SubjectGroup;
use App\Modules\Subject\Models\SubjectType;
use App\Modules\Lookup\Models\LookupCategory;
use App\Modules\Lookup\Models\LookupValue;
use App\Modules\Student\Models\Guardian;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentBatch;
use App\Modules\Academic\Models\AcademicSession;
use App\Modules\Admission\Models\AdmissionSession;
use App\Modules\Admission\Models\Applicant;
use App\Modules\Admission\Models\EligibilityRuleType;
use App\Modules\Admission\Models\OfferedProgram;
use App\Modules\Admission\Models\ProgramQuotaSeat;
use App\Modules\Admission\Models\AdmissionPreferenceGroup;
use App\Modules\Admission\Models\AdmissionPreferenceGroupProgram;
use App\Modules\Admission\Models\ApplicantDocument;
use App\Modules\Admission\Models\ApplicantProgramApplication;
use App\Modules\Admission\Models\ApplicantQualification;
use App\Modules\Assessment\Models\Assessment;
use App\Modules\Assessment\Models\AssessmentCategory;
use App\Modules\Assessment\Models\AssessmentParticipant;
use App\Modules\Assessment\Models\AssessmentQuestion;
use App\Modules\Assessment\Models\AssessmentSchedule;
use App\Modules\Assessment\Models\AssessmentSection;
use App\Modules\Assessment\Models\AssessmentSubject;
use App\Modules\Assessment\Models\AssessmentTopic;
use App\Modules\Assessment\Models\Question;
use App\Modules\Assessment\Models\QuestionBank;

class DynamicOptionController extends Controller
{
    public function campuses(Request $request): JsonResponse
    {
        return $this->options($request, Campus::class);
    }

    public function buildings(Request $request): JsonResponse
    {
        return $this->options($request, Building::class, [
            'campus_id',
        ]);
    }

    public function floors(Request $request): JsonResponse
    {
        return $this->options($request, Floor::class, [
            'campus_id',
            'building_id',
        ]);
    }

    public function faculties(Request $request): JsonResponse
    {
        return $this->options($request, Faculty::class);
    }

    public function institutes(Request $request): JsonResponse
    {
        return $this->options($request, Institute::class, [
            'faculty_id',
        ]);
    }

    public function departments(Request $request): JsonResponse
    {
        return $this->options($request, Department::class, [
            'faculty_id',
            'institute_id',
        ]);
    }

    public function programLevels(Request $request): JsonResponse
    {
        return $this->options($request, ProgramLevel::class);
    }

public function academicSessions(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = AcademicSession::query();

    if ($tenantId) {
        $query->where('tenant_id', $tenantId);
    }

    if ($request->filled('status')) {
        $query->where('status', $request->query('status'));
    }

    $items = $query
        ->orderBy('id', 'desc')
        ->get()
        ->map(fn ($item) => [
            'label' => $item->name ?? $item->title ?? $item->code ?? ('Session #' . $item->id),
            'value' => $item->id,
            'code' => $item->code ?? null,
            'name' => $item->name ?? null,
        ])
        ->values();

    return \App\Helpers\ApiResponse::success(
        $items,
        'Academic session options fetched successfully.'
    );
}
    public function programs(Request $request): JsonResponse
    {
        return $this->options($request, Program::class, [
            'faculty_id',
            'institute_id',
            'department_id',
            'program_level_id',
        ]);
    }
public function lookups(Request $request, string $categoryCode): \Illuminate\Http\JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $category = \App\Modules\Lookup\Models\LookupCategory::query()
        ->where('code', $categoryCode)
        ->where('status', 'active')
        ->where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id');

            if ($tenantId) {
                $q->orWhere('tenant_id', $tenantId);
            }
        })
        ->orderByRaw('tenant_id IS NULL')
        ->first();

    if (!$category) {
        return \App\Helpers\ApiResponse::success(
            [],
            "Lookup category {$categoryCode} not found."
        );
    }

    $query = \App\Modules\Lookup\Models\LookupValue::query()
        ->where('lookup_category_id', $category->id)
        ->where('status', 'active')
        ->where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id');

            if ($tenantId) {
                $q->orWhere('tenant_id', $tenantId);
            }
        });

    if ($request->filled('parent_id')) {
        $query->where('parent_id', $request->query('parent_id'));
    }

    $items = $query
        ->orderBy('display_order')
        ->orderBy('name')
        ->get()
        ->map(fn ($item) => [
            'label' => $item->name,
            'value' => $item->id,
            'id' => $item->id,
            'code' => $item->code,
            'name' => $item->name,
            'parent_id' => $item->parent_id,
            'tenant_id' => $item->tenant_id,
            'is_global' => $item->tenant_id === null,
        ])
        ->values();

    return \App\Helpers\ApiResponse::success(
        $items,
        'Lookup options fetched successfully.'
    );
}
public function lookupCategories(Request $request): JsonResponse
{
    return $this->options($request, LookupCategory::class);
}

public function lookupValues(Request $request): JsonResponse
{
    return $this->options($request, LookupValue::class, [
        'lookup_category_id',
        'parent_id',
    ]);
}
    public function academicTerms(Request $request): JsonResponse
    {
        return $this->options($request, AcademicTerm::class, [
            'program_id',
        ]);
    }

    private function options(
        Request $request,
        string $modelClass,
        array $allowedParentFilters = []
    ): JsonResponse {
        $model = new $modelClass();
        $table = $model->getTable();

        $query = $modelClass::query();

        $this->applyTenantScope($query, $table);

        if (Schema::hasColumn($table, 'status')) {
            $query->where('status', 'active');
        }

        foreach ($allowedParentFilters as $field) {
            $value = $request->query($field);

            if ($value !== null && $value !== '') {
                $query->where($field, $value);
            }
        }

        $items = $query
            ->orderBy('name')
            ->get()
            ->map(fn ($item) => [
                'label' => $item->name,
                'value' => $item->id,
                'code' => $item->code,
            ])
            ->values();

        return ApiResponse::success(
            $items,
            'Options fetched successfully.'
        );
    }

    private function applyTenantScope(Builder $query, string $table): void
{
    if (!auth()->check()) {
        return;
    }

    $user = auth()->user();
    $tenantId = $user?->tenant_id;

    if (!$tenantId) {
        return;
    }

    if (!Schema::hasColumn($table, 'tenant_id')) {
        return;
    }

    if (in_array($table, ['lookup_categories', 'lookup_values'], true)) {
        $query->where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id')
                ->orWhere('tenant_id', $tenantId);
        });

        return;
    }

    $query->where('tenant_id', $tenantId);
}
public function admissionSessions(Request $request): JsonResponse
{
    return $this->options($request, AdmissionSession::class);
}
public function applicantQualifications(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = ApplicantQualification::query();

    if ($tenantId) {
        $query->where('tenant_id', $tenantId);
    }

    if ($request->filled('applicant_id')) {
        $query->where('applicant_id', $request->query('applicant_id'));
    }

    $items = $query
        ->with(['qualificationLevel'])
        ->orderByDesc('id')
        ->limit(100)
        ->get()
        ->map(fn ($item) => [
            'label' => trim(($item->qualificationLevel?->name ?? 'Qualification') . ' - ' . ($item->degree_class_name ?? $item->passing_year ?? $item->id)),
            'value' => $item->id,
            'id' => $item->id,
            'applicant_id' => $item->applicant_id,
            'qualification_level_id' => $item->qualification_level_id,
            'degree_class_name' => $item->degree_class_name,
            'percentage' => $item->percentage,
            'cgpa' => $item->cgpa,
        ])
        ->values();

    return \App\Helpers\ApiResponse::success($items, 'Applicant qualification options fetched successfully.');
}

public function applicantProgramApplications(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = ApplicantProgramApplication::query();

    if ($tenantId) {
        $query->where('tenant_id', $tenantId);
    }

    if ($request->filled('applicant_id')) {
        $query->where('applicant_id', $request->query('applicant_id'));
    }

    if ($request->filled('offered_program_id')) {
        $query->where('offered_program_id', $request->query('offered_program_id'));
    }

    $items = $query
        ->orderByDesc('id')
        ->limit(100)
        ->get()
        ->map(fn ($item) => [
            'label' => $item->application_no ?: 'Application #' . $item->id,
            'value' => $item->id,
            'id' => $item->id,
            'applicant_id' => $item->applicant_id,
            'offered_program_id' => $item->offered_program_id,
            'program_quota_seat_id' => $item->program_quota_seat_id,
            'application_status_code' => $item->application_status_code,
        ])
        ->values();

    return \App\Helpers\ApiResponse::success($items, 'Applicant program application options fetched successfully.');
}

public function applicantDocuments(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = ApplicantDocument::query();

    if ($tenantId) {
        $query->where('tenant_id', $tenantId);
    }

    if ($request->filled('applicant_id')) {
        $query->where('applicant_id', $request->query('applicant_id'));
    }

    $items = $query
        ->orderByDesc('id')
        ->limit(100)
        ->get()
        ->map(fn ($item) => [
            'label' => $item->document_title,
            'value' => $item->id,
            'id' => $item->id,
            'applicant_id' => $item->applicant_id,
            'document_title' => $item->document_title,
            'verification_status_code' => $item->verification_status_code,
        ])
        ->values();

    return \App\Helpers\ApiResponse::success($items, 'Applicant document options fetched successfully.');
}
public function offeredPrograms(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = OfferedProgram::query();

    if ($tenantId) {
        $query->where('tenant_id', $tenantId);
    }

    if ($request->filled('admission_session_id')) {
        $query->where('admission_session_id', $request->query('admission_session_id'));
    }

    if ($request->filled('program_id')) {
        $query->where('program_id', $request->query('program_id'));
    }

    if ($request->filled('status_code')) {
        $query->where('status_code', $request->query('status_code'));
    }

    $items = $query
        ->orderBy('title')
        ->get()
        ->map(fn ($item) => [
            'label' => $item->code . ' - ' . $item->title,
            'value' => $item->id,
            'id' => $item->id,
            'code' => $item->code,
            'title' => $item->title,
            'admission_session_id' => $item->admission_session_id,
            'program_id' => $item->program_id,
            'status_code' => $item->status_code,
        ])
        ->values();

    return \App\Helpers\ApiResponse::success($items, 'Offered program options fetched successfully.');
}

public function programQuotaSeats(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = ProgramQuotaSeat::query();

    if ($tenantId) {
        $query->where('tenant_id', $tenantId);
    }

    if ($request->filled('offered_program_id')) {
        $query->where('offered_program_id', $request->query('offered_program_id'));
    }

    $items = $query
        ->orderBy('display_order')
        ->orderBy('quota_name')
        ->get()
        ->map(fn ($item) => [
            'label' => $item->quota_code . ' - ' . $item->quota_name,
            'value' => $item->id,
            'id' => $item->id,
            'quota_code' => $item->quota_code,
            'quota_name' => $item->quota_name,
            'offered_program_id' => $item->offered_program_id,
            'allocated_seats' => $item->allocated_seats,
            'available_seats' => $item->available_seats,
        ])
        ->values();

    return \App\Helpers\ApiResponse::success($items, 'Program quota seat options fetched successfully.');
}

public function eligibilityRuleTypes(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = EligibilityRuleType::withoutGlobalScopes()
        ->where('is_active', true)
        ->where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id');

            if ($tenantId) {
                $q->orWhere('tenant_id', $tenantId);
            }
        });

    $items = $query
        ->orderBy('display_order')
        ->orderBy('name')
        ->get()
        ->map(fn ($item) => [
            'label' => $item->name,
            'value' => $item->id,
            'id' => $item->id,
            'code' => $item->code,
            'name' => $item->name,
            'source_area' => $item->source_area,
            'evaluator_key' => $item->evaluator_key,
        ])
        ->values();

    return \App\Helpers\ApiResponse::success($items, 'Eligibility rule type options fetched successfully.');
}

public function applicants(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = Applicant::query();

    if ($tenantId) {
        $query->where('tenant_id', $tenantId);
    }

    if ($request->filled('q')) {
        $search = $request->query('q');

        $query->where(function ($q) use ($search) {
            $q->where('applicant_no', 'like', "%{$search}%")
                ->orWhere('full_name', 'like', "%{$search}%")
                ->orWhere('father_name', 'like', "%{$search}%")
                ->orWhere('cnic_bform', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    $items = $query
        ->orderBy('full_name')
        ->limit(100)
        ->get()
        ->map(fn ($item) => [
            'label' => trim(($item->applicant_no ? $item->applicant_no . ' - ' : '') . $item->full_name),
            'value' => $item->id,
            'id' => $item->id,
            'applicant_no' => $item->applicant_no,
            'full_name' => $item->full_name,
            'father_name' => $item->father_name,
            'cnic_bform' => $item->cnic_bform,
        ])
        ->values();

    return \App\Helpers\ApiResponse::success($items, 'Applicant options fetched successfully.');
}
public function students(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = Student::query()
        ->where('student_status', 'active');

    if ($tenantId) {
        $query->where('tenant_id', $tenantId);
    }

    if ($request->filled('q')) {
        $search = $request->query('q');

        $query->where(function ($q) use ($search) {
            $q->where('student_no', 'like', "%{$search}%")
                ->orWhere('admission_no', 'like', "%{$search}%")
                ->orWhere('full_name', 'like', "%{$search}%")
                ->orWhere('father_name', 'like', "%{$search}%")
                ->orWhere('cnic_bform', 'like', "%{$search}%");
        });
    }

    $items = $query
        ->orderBy('full_name')
        ->limit(100)
        ->get()
        ->map(fn ($item) => [
            'label' => trim(($item->student_no ? $item->student_no . ' - ' : '') . $item->full_name),
            'value' => $item->id,
            'student_no' => $item->student_no,
            'admission_no' => $item->admission_no,
            'father_name' => $item->father_name,
        ])
        ->values();

    return \App\Helpers\ApiResponse::success($items, 'Student options fetched successfully.');
}

public function guardians(Request $request): JsonResponse
{
    return $this->options($request, Guardian::class);
}

public function studentBatches(Request $request): JsonResponse
{
    return $this->options($request, StudentBatch::class, [
        'academic_session_id',
        'faculty_id',
        'institute_id',
        'department_id',
        'program_id',
        'curriculum_id',
    ]);
}
    public function subjectTypes(Request $request): JsonResponse
{
    return $this->options($request, SubjectType::class);
}

public function subjectGroups(Request $request): JsonResponse
{
    return $this->options($request, SubjectGroup::class);
}

public function subjects(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = \App\Modules\Subject\Models\Subject::query()
        ->where('status', 'active');

    if ($tenantId) {
        $query->where('tenant_id', $tenantId);
    }

    if ($request->filled('subject_type_id')) {
        $query->where('subject_type_id', $request->query('subject_type_id'));
    }

    if ($request->filled('subject_group_id')) {
        $query->where('subject_group_id', $request->query('subject_group_id'));
    }

    $items = $query
        ->orderBy('code')
        ->orderBy('name')
        ->get()
        ->map(fn ($item) => [
            'label' => $item->code . ' - ' . $item->name,
            'value' => $item->id,
            'code' => $item->code,
            'name' => $item->name,
            'short_name' => $item->short_name,

            'subject_nature' => $item->subject_nature,
            'credit_hours' => $item->credit_hours,
            'theory_hours' => $item->theory_hours,
            'practical_hours' => $item->practical_hours,
            'tutorial_hours' => $item->tutorial_hours,

            'total_marks' => $item->total_marks,
            'passing_marks' => $item->passing_marks,

            'is_compulsory' => (bool) $item->is_compulsory,
            'is_credit_subject' => (bool) $item->is_credit_subject,
        ])
        ->values();

    return \App\Helpers\ApiResponse::success(
        $items,
        'Subject options fetched successfully.'
    );
}

public function curriculums(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = \App\Modules\Subject\Models\Curriculum::query()
        ->where('status', 'active');

    if ($tenantId) {
        $query->where('tenant_id', $tenantId);
    }

    if ($request->filled('program_id')) {
        $query->where('program_id', $request->query('program_id'));
    }

    $items = $query
        ->orderBy('name')
        ->get()
        ->map(fn ($item) => [
            'label' => $item->name,
            'value' => $item->id,
            'code' => $item->code,
            'program_id' => $item->program_id,
        ])
        ->values();

    return \App\Helpers\ApiResponse::success(
        $items,
        'Curriculum options fetched successfully.'
    );
}
public function admissionPreferenceGroups(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = AdmissionPreferenceGroup::query()
        ->where('status_code', 'active');

    if ($tenantId) {
        $query->where('tenant_id', $tenantId);
    }

    if ($request->filled('admission_session_id')) {
        $query->where('admission_session_id', $request->query('admission_session_id'));
    }

    if ($request->filled('q')) {
        $search = $request->query('q');

        $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%");
        });
    }

    $items = $query
        ->orderByDesc('is_default')
        ->orderBy('display_order')
        ->orderBy('name')
        ->get()
        ->map(fn ($item) => [
            'label' => $item->code . ' - ' . $item->name,
            'value' => $item->id,
            'id' => $item->id,
            'admission_session_id' => $item->admission_session_id,
            'code' => $item->code,
            'name' => $item->name,
            'min_preferences' => $item->min_preferences,
            'max_preferences' => $item->max_preferences,
            'is_default' => (bool) $item->is_default,
        ])
        ->values();

    return ApiResponse::success($items, 'Admission preference group options fetched successfully.');
}
public function admissionMeritFormulas(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    if (!Schema::hasTable('admission_merit_formulas')) {
        return ApiResponse::success([], 'Merit formula table not found.');
    }

    $query = \Illuminate\Support\Facades\DB::table('admission_merit_formulas');

    if ($tenantId && Schema::hasColumn('admission_merit_formulas', 'tenant_id')) {
        $query->where('tenant_id', $tenantId);
    }

    if (
        $request->filled('admission_session_id') &&
        Schema::hasColumn('admission_merit_formulas', 'admission_session_id')
    ) {
        $query->where('admission_session_id', $request->query('admission_session_id'));
    }

    if ($request->filled('status_code') && Schema::hasColumn('admission_merit_formulas', 'status_code')) {
        $query->where('status_code', $request->query('status_code'));
    } elseif (Schema::hasColumn('admission_merit_formulas', 'status_code')) {
        $query->whereIn('status_code', ['active', 'published']);
    }

    if ($request->filled('q')) {
        $search = $request->query('q');

        $query->where(function ($q) use ($search) {
            foreach (['code', 'formula_code', 'title', 'name', 'formula_name'] as $column) {
                if (Schema::hasColumn('admission_merit_formulas', $column)) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            }
        });
    }

    $items = $query
        ->orderByDesc('id')
        ->limit(200)
        ->get()
        ->map(function ($item) {
            $code = $item->code
                ?? $item->formula_code
                ?? null;

            $title = $item->title
                ?? $item->name
                ?? $item->formula_name
                ?? $item->description
                ?? ('Formula #' . $item->id);

            return [
                'label' => trim(($code ? $code . ' - ' : '') . $title),
                'value' => (int) $item->id,
                'id' => (int) $item->id,
                'code' => $code,
                'title' => $title,
                'admission_session_id' => $item->admission_session_id ?? null,
                'formula_type_code' => $item->formula_type_code ?? null,
                'status_code' => $item->status_code ?? null,
            ];
        })
        ->values();

    return ApiResponse::success($items, 'Merit formula options fetched successfully.');
}
public function admissionPreferenceGroupPrograms(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = AdmissionPreferenceGroupProgram::query()
        ->with(['preferenceGroup', 'offeredProgram'])
        ->where('status_code', 'active');

    if ($tenantId) {
        $query->where('tenant_id', $tenantId);
    }

    if ($request->filled('admission_preference_group_id')) {
        $query->where('admission_preference_group_id', $request->query('admission_preference_group_id'));
    }

    if ($request->filled('offered_program_id')) {
        $query->where('offered_program_id', $request->query('offered_program_id'));
    }

    $items = $query
        ->orderBy('display_order')
        ->get()
        ->map(fn ($item) => [
            'label' => ($item->preferenceGroup?->name ?? '-') . ' / ' . ($item->offeredProgram?->title ?? '-'),
            'value' => $item->id,
            'id' => $item->id,
            'admission_preference_group_id' => $item->admission_preference_group_id,
            'offered_program_id' => $item->offered_program_id,
            'group_name' => $item->preferenceGroup?->name,
            'offered_program_title' => $item->offeredProgram?->title,
        ])
        ->values();

    return ApiResponse::success($items, 'Admission preference group program options fetched successfully.');
}
public function assessmentCategories(Request $request): JsonResponse
{
    return $this->options($request, AssessmentCategory::class);
}

public function questionBanks(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = QuestionBank::query();

    if ($tenantId && Schema::hasColumn('question_banks', 'tenant_id')) {
        $query->where('tenant_id', $tenantId);
    }

    if (Schema::hasColumn('question_banks', 'status_code')) {
        $query->whereIn('status_code', ['active', 'published']);
    }

    if (Schema::hasColumn('question_banks', 'deleted_at')) {
        $query->whereNull('deleted_at');
    }

    $items = $query
        ->orderBy('name')
        ->get()
        ->map(fn ($item) => [
            'label' => ($item->code ? $item->code . ' - ' : '') . $item->name,
            'value' => $item->id,
            'id' => $item->id,
            'code' => $item->code,
            'name' => $item->name,
        ])
        ->values();

    return ApiResponse::success($items, 'Question bank options fetched successfully.');
}

public function assessmentSubjects(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = AssessmentSubject::query();

    if ($tenantId && Schema::hasColumn('assessment_subjects', 'tenant_id')) {
        $query->where('tenant_id', $tenantId);
    }

    if (Schema::hasColumn('assessment_subjects', 'status_code')) {
        $query->whereIn('status_code', ['active', 'published']);
    }

    if (Schema::hasColumn('assessment_subjects', 'deleted_at')) {
        $query->whereNull('deleted_at');
    }

    $items = $query
        ->orderBy('name')
        ->get()
        ->map(fn ($item) => [
            'label' => ($item->code ? $item->code . ' - ' : '') . $item->name,
            'value' => $item->id,
            'id' => $item->id,
            'code' => $item->code,
            'name' => $item->name,
        ])
        ->values();

    return ApiResponse::success($items, 'Assessment subject options fetched successfully.');
}

public function assessmentTopics(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = AssessmentTopic::query();

    if ($tenantId && Schema::hasColumn('assessment_topics', 'tenant_id')) {
        $query->where('tenant_id', $tenantId);
    }

    if ($request->filled('assessment_subject_id')) {
        $query->where('assessment_subject_id', $request->query('assessment_subject_id'));
    }

    if (Schema::hasColumn('assessment_topics', 'status_code')) {
        $query->whereIn('status_code', ['active', 'published']);
    }

    if (Schema::hasColumn('assessment_topics', 'deleted_at')) {
        $query->whereNull('deleted_at');
    }

    $items = $query
        ->orderBy('name')
        ->get()
        ->map(fn ($item) => [
            'label' => ($item->code ? $item->code . ' - ' : '') . $item->name,
            'value' => $item->id,
            'id' => $item->id,
            'code' => $item->code,
            'name' => $item->name,
            'assessment_subject_id' => $item->assessment_subject_id,
        ])
        ->values();

    return ApiResponse::success($items, 'Assessment topic options fetched successfully.');
}

public function questions(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = Question::query()
        ->where('is_active', true);

    if ($tenantId) {
        $query->where('tenant_id', $tenantId);
    }

    if ($request->filled('question_bank_id')) {
        $query->where('question_bank_id', $request->query('question_bank_id'));
    }

    if ($request->filled('assessment_subject_id')) {
        $query->where('assessment_subject_id', $request->query('assessment_subject_id'));
    }

    if ($request->filled('assessment_topic_id')) {
        $query->where('assessment_topic_id', $request->query('assessment_topic_id'));
    }

    if ($request->filled('question_type_code')) {
        $query->where('question_type_code', $request->query('question_type_code'));
    }

    if ($request->filled('q')) {
        $search = $request->query('q');

        $query->where(function ($q) use ($search) {
            $q->where('question_text', 'like', "%{$search}%")
                ->orWhere('external_ref_no', 'like', "%{$search}%")
                ->orWhere('import_batch_no', 'like', "%{$search}%");
        });
    }

    $items = $query
        ->orderByDesc('id')
        ->limit(100)
        ->get()
        ->map(fn ($item) => [
            'label' => str($item->question_text)->limit(100)->toString(),
            'value' => $item->id,
            'id' => $item->id,
            'question_bank_id' => $item->question_bank_id,
            'assessment_subject_id' => $item->assessment_subject_id,
            'assessment_topic_id' => $item->assessment_topic_id,
            'question_type_code' => $item->question_type_code,
            'difficulty_code' => $item->difficulty_code,
            'default_marks' => $item->default_marks,
        ])
        ->values();

    return ApiResponse::success($items, 'Question options fetched successfully.');
}

public function assessments(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = Assessment::query();

    if ($tenantId) {
        $query->where('tenant_id', $tenantId);
    }

    if ($request->filled('assessment_category_id')) {
        $query->where('assessment_category_id', $request->query('assessment_category_id'));
    }

    if ($request->filled('purpose_code')) {
        $query->where('purpose_code', $request->query('purpose_code'));
    }

    if ($request->filled('mode_code')) {
        $query->where('mode_code', $request->query('mode_code'));
    }

    if ($request->filled('admission_session_id')) {
        $query->where('admission_session_id', $request->query('admission_session_id'));
    }

    if ($request->filled('status_code')) {
        $query->where('status_code', $request->query('status_code'));
    }

    if ($request->filled('q')) {
        $search = $request->query('q');

        $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%");
        });
    }

    $items = $query
        ->orderByDesc('id')
        ->limit(200)
        ->get()
        ->map(fn ($item) => [
            'label' => trim(($item->code ? $item->code . ' - ' : '') . $item->title),
            'value' => $item->id,
            'id' => $item->id,
            'code' => $item->code,
            'title' => $item->title,
            'purpose_code' => $item->purpose_code,
            'mode_code' => $item->mode_code,
            'status_code' => $item->status_code,
            'assessment_category_id' => $item->assessment_category_id,
            'admission_session_id' => $item->admission_session_id,
            'total_marks' => $item->total_marks,
            'duration_minutes' => $item->duration_minutes,
        ])
        ->values();

    return ApiResponse::success($items, 'Assessment options fetched successfully.');
}

public function assessmentSections(Request $request): JsonResponse
{
    return $this->options($request, AssessmentSection::class, [
        'assessment_id',
        'assessment_subject_id',
    ]);
}

public function assessmentQuestions(Request $request): JsonResponse
{
    return $this->options($request, AssessmentQuestion::class, [
        'assessment_id',
        'assessment_section_id',
        'question_id',
    ]);
}

public function assessmentSchedules(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = AssessmentSchedule::query();

    if ($tenantId) {
        $query->where('tenant_id', $tenantId);
    }

    if ($request->filled('assessment_id')) {
        $query->where('assessment_id', $request->query('assessment_id'));
    }

    if ($request->filled('mode_code')) {
        $query->where('mode_code', $request->query('mode_code'));
    }

    if ($request->filled('status_code')) {
        $query->where('status_code', $request->query('status_code'));
    }

    if ($request->filled('q')) {
        $search = $request->query('q');

        $query->where(function ($q) use ($search) {
            $q->where('schedule_code', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%")
                ->orWhere('venue_name', 'like', "%{$search}%");
        });
    }

    $items = $query
        ->orderByDesc('id')
        ->limit(200)
        ->get()
        ->map(fn ($item) => [
            'label' => trim(($item->schedule_code ? $item->schedule_code . ' - ' : '') . $item->title),
            'value' => $item->id,
            'id' => $item->id,
            'assessment_id' => $item->assessment_id,
            'schedule_code' => $item->schedule_code,
            'title' => $item->title,
            'start_at' => $item->start_at,
            'end_at' => $item->end_at,
            'mode_code' => $item->mode_code,
            'status_code' => $item->status_code,
            'venue_name' => $item->venue_name,
        ])
        ->values();

    return ApiResponse::success($items, 'Assessment schedule options fetched successfully.');
}

public function assessmentParticipants(Request $request): JsonResponse
{
    $tenantId = auth()->user()?->tenant_id;

    $query = AssessmentParticipant::query();

    if ($tenantId) {
        $query->where('tenant_id', $tenantId);
    }

    if ($request->filled('assessment_id')) {
        $query->where('assessment_id', $request->query('assessment_id'));
    }

    if ($request->filled('assessment_schedule_id')) {
        $query->where('assessment_schedule_id', $request->query('assessment_schedule_id'));
    }

    if ($request->filled('participant_type_code')) {
        $query->where('participant_type_code', $request->query('participant_type_code'));
    }

    $items = $query
        ->orderByDesc('id')
        ->limit(100)
        ->get()
        ->map(fn ($item) => [
            'label' => ($item->roll_no ?: 'Participant #' . $item->id) . ' - ' . $item->participant_type_code,
            'value' => $item->id,
            'id' => $item->id,
            'assessment_id' => $item->assessment_id,
            'assessment_schedule_id' => $item->assessment_schedule_id,
            'participant_type_code' => $item->participant_type_code,
            'participant_id' => $item->participant_id,
            'applicant_id' => $item->applicant_id,
            'roll_no' => $item->roll_no,
        ])
        ->values();

    return ApiResponse::success($items, 'Assessment participant options fetched successfully.');
}
}