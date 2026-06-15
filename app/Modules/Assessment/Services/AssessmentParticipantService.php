<?php

namespace App\Modules\Assessment\Services;

use App\Modules\Admission\Models\Applicant;
use App\Modules\Assessment\Models\Assessment;
use App\Modules\Assessment\Models\AssessmentParticipant;
use App\Modules\Assessment\Models\AssessmentSchedule;
use Illuminate\Support\Facades\DB;

class AssessmentParticipantService
{
    public function candidates(array $filters): array
    {
        $tenantId = $this->tenantId();

        $query = Applicant::query()
            ->where('tenant_id', $tenantId);

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('applicant_no', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('cnic_bform', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['applicant_status_code'])) {
            $query->where('applicant_status_code', $filters['applicant_status_code']);
        }

        if (!empty($filters['profile_status_code'])) {
            $query->where('profile_status_code', $filters['profile_status_code']);
        }

        return $query
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 20))
            ->toArray();
    }

    public function list(array $filters): array
    {
        $tenantId = $this->tenantId();

        $query = AssessmentParticipant::query()
            ->with(['assessment:id,code,title', 'schedule:id,schedule_code,title,start_at,end_at'])
            ->where('tenant_id', $tenantId);

        if (!empty($filters['assessment_id'])) {
            $query->where('assessment_id', $filters['assessment_id']);
        }

        if (!empty($filters['assessment_schedule_id'])) {
            $query->where('assessment_schedule_id', $filters['assessment_schedule_id']);
        }

        if (!empty($filters['participant_type_code'])) {
            $query->where('participant_type_code', $filters['participant_type_code']);
        }

        if (!empty($filters['attempt_status_code'])) {
            $query->where('attempt_status_code', $filters['attempt_status_code']);
        }

        if (!empty($filters['result_status_code'])) {
            $query->where('result_status_code', $filters['result_status_code']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('roll_no', 'like', "%{$search}%")
                    ->orWhere('seat_no', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 20))
            ->toArray();
    }

    public function bulkAssignApplicants(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $this->tenantId();

            $assessment = Assessment::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['assessment_id'])
                ->firstOrFail();

            $schedule = null;

            if (!empty($data['assessment_schedule_id'])) {
                $schedule = AssessmentSchedule::query()
                    ->where('tenant_id', $tenantId)
                    ->where('assessment_id', $assessment->id)
                    ->where('id', $data['assessment_schedule_id'])
                    ->firstOrFail();
            }

            $applicantIds = array_values(array_unique(array_filter($data['applicant_ids'] ?? [])));

            if (count($applicantIds) === 0) {
                abort(422, 'Please select at least one applicant.');
            }

            $applicants = Applicant::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $applicantIds)
                ->get();

            $assigned = 0;
            $skipped = 0;

            foreach ($applicants as $applicant) {
                $exists = AssessmentParticipant::query()
                    ->where('tenant_id', $tenantId)
                    ->where('assessment_id', $assessment->id)
                    ->where('participant_type_code', 'applicant')
                    ->where('participant_id', $applicant->id)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                AssessmentParticipant::create([
                    'tenant_id' => $tenantId,
                    'assessment_id' => $assessment->id,
                    'assessment_schedule_id' => $schedule?->id,
                    'participant_type_code' => 'applicant',
                    'participant_id' => $applicant->id,
                    'applicant_id' => $applicant->id,

                    'roll_no' => null,
                    'seat_no' => null,

                    'attendance_status_code' => 'pending',
                    'attempt_status_code' => 'not_started',
                    'result_status_code' => 'pending',

                    'assigned_at' => now(),
                    'remarks' => $data['remarks'] ?? null,
                    'import_batch_no' => $data['import_batch_no'] ?? null,
                ]);

                $assigned++;
            }

            return [
                'assigned' => $assigned,
                'skipped_existing' => $skipped,
            ];
        });
    }

    public function generateRollNumbers(int $assessmentId, ?int $scheduleId = null): array
    {
        return DB::transaction(function () use ($assessmentId, $scheduleId) {
            $tenantId = $this->tenantId();

            $assessment = Assessment::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $assessmentId)
                ->firstOrFail();

            $query = AssessmentParticipant::query()
                ->where('tenant_id', $tenantId)
                ->where('assessment_id', $assessment->id)
                ->whereNull('roll_no');

            if ($scheduleId) {
                $query->where('assessment_schedule_id', $scheduleId);
            }

            $participants = $query
                ->orderBy('id')
                ->get();

            $generated = 0;

            foreach ($participants as $participant) {
                $participant->update([
                    'roll_no' => $this->nextRollNo($tenantId, $assessment),
                ]);

                $generated++;
            }

            return [
                'generated' => $generated,
            ];
        });
    }

    public function rollNoSlip(int $participantId): array
    {
        $tenantId = $this->tenantId();

        $participant = AssessmentParticipant::query()
            ->with(['assessment', 'schedule'])
            ->where('tenant_id', $tenantId)
            ->where('id', $participantId)
            ->firstOrFail();

        $applicant = null;

        if ($participant->participant_type_code === 'applicant' && $participant->applicant_id) {
            $applicant = Applicant::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $participant->applicant_id)
                ->first();
        }

        return [
            'participant' => $participant,
            'assessment' => $participant->assessment,
            'schedule' => $participant->schedule,
            'applicant' => $applicant,
            'instructions' => [
                'Bring original CNIC / B-Form.',
                'Bring printed roll no slip.',
                'Reach the center before reporting time.',
                'Mobile phones and electronic devices are not allowed unless permitted.',
            ],
        ];
    }

    private function nextRollNo(int $tenantId, Assessment $assessment): string
    {
        $prefix = 'RN-' . $assessment->id . '-';

        $lastRollNo = AssessmentParticipant::query()
            ->where('tenant_id', $tenantId)
            ->where('assessment_id', $assessment->id)
            ->where('roll_no', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('roll_no');

        $next = 1;

        if ($lastRollNo) {
            $lastNumeric = (int) str_replace($prefix, '', $lastRollNo);
            $next = $lastNumeric + 1;
        }

        do {
            $rollNo = $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);

            $exists = AssessmentParticipant::query()
                ->where('tenant_id', $tenantId)
                ->where('roll_no', $rollNo)
                ->exists();

            $next++;
        } while ($exists);

        return $rollNo;
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