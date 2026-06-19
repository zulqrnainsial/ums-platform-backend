<?php

namespace App\Modules\Attendance\Services;

use Illuminate\Support\Facades\DB;

class AttendanceSessionService
{
    public function index(array $filters = []): array
    {
        $tenantId = $this->tenantId();

        $query = DB::table('attendance_sessions as ats')
            ->leftJoin('academic_sessions as acs', 'acs.id', '=', 'ats.academic_session_id')
            ->leftJoin('academic_terms as act', 'act.id', '=', 'ats.academic_term_id')
            ->leftJoin('programs as p', 'p.id', '=', 'ats.program_id')
            ->leftJoin('student_batches as sb', 'sb.id', '=', 'ats.student_batch_id')
            ->leftJoin('sections as sec', 'sec.id', '=', 'ats.section_id')
            ->where('ats.tenant_id', $tenantId)
            ->whereNull('ats.deleted_at');

        foreach ([
            'academic_session_id',
            'academic_term_id',
            'program_id',
            'student_batch_id',
            'section_id',
            'curriculum_subject_id',
            'status_code',
        ] as $field) {
            if (!empty($filters[$field])) {
                $query->where("ats.$field", $filters[$field]);
            }
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('ats.attendance_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('ats.attendance_date', '<=', $filters['date_to']);
        }

        return $query
            ->select([
                'ats.*',
                'acs.name as academic_session_name',
                'act.name as academic_term_name',
                'p.name as program_name',
                'sb.name as batch_name',
                'sec.name as section_name',
            ])
            ->orderByDesc('ats.attendance_date')
            ->orderByDesc('ats.id')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->toArray();
    }

    public function show(int $sessionId): array
    {
        $tenantId = $this->tenantId();

        $session = DB::table('attendance_sessions')
            ->where('tenant_id', $tenantId)
            ->where('id', $sessionId)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$session, 404, 'Attendance session not found.');

        $records = DB::table('attendance_records as ar')
            ->join('students as s', 's.id', '=', 'ar.student_id')
            ->leftJoin('student_enrollments as se', 'se.id', '=', 'ar.student_enrollment_id')
            ->where('ar.attendance_session_id', $sessionId)
            ->select([
                'ar.*',
                's.student_no',
                's.full_name as student_name',
                'se.roll_no',
                'se.registration_no',
            ])
            ->orderBy('se.roll_sequence_no')
            ->orderBy('s.full_name')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();

        return [
            'session' => (array) $session,
            'records' => $records,
        ];
    }

    public function destroy(int $sessionId): array
    {
        $tenantId = $this->tenantId();

        $session = DB::table('attendance_sessions')
            ->where('tenant_id', $tenantId)
            ->where('id', $sessionId)
            ->first();

        abort_if(!$session, 404, 'Attendance session not found.');
        abort_if($session->status_code === 'locked', 423, 'Locked session cannot be deleted.');

        DB::table('attendance_sessions')
            ->where('id', $sessionId)
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
                'updated_by' => auth()->id(),
            ]);

        return ['attendance_session_id' => $sessionId];
    }

    private function tenantId(): int
    {
        $user = auth()->user();

        return (int) ($user?->tenant_id ?? 0);
    }
}