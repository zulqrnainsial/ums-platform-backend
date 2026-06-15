<?php

namespace App\Modules\Subject\Services;

use App\Modules\Subject\Models\Curriculum;
use App\Modules\Subject\Models\CurriculumSubject;
use App\Modules\Subject\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CurriculumSubjectBulkService
{
    public function bulkAssign(array $data): array
    {
        $user = auth()->user();

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        if (!$user->can('subject.curriculum_subject.create')) {
            abort(403, 'You are not allowed to assign curriculum subjects.');
        }

        $tenantId = $user->tenant_id;

        if (!$tenantId) {
            throw ValidationException::withMessages([
                'tenant_id' => ['Tenant context is required.'],
            ]);
        }

        return DB::transaction(function () use ($data, $tenantId, $user) {
            $curriculum = Curriculum::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['curriculum_id'])
                ->first();

            if (!$curriculum) {
                throw ValidationException::withMessages([
                    'curriculum_id' => ['Invalid curriculum selected.'],
                ]);
            }

            if ((int) $curriculum->program_id !== (int) $data['program_id']) {
                throw ValidationException::withMessages([
                    'program_id' => ['Selected program does not belong to the selected curriculum.'],
                ]);
            }

            $items = collect($data['items']);

            $subjectIds = $items
                ->pluck('subject_id')
                ->unique()
                ->values()
                ->toArray();

            $subjects = Subject::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $subjectIds)
                ->where('status', 'active')
                ->get()
                ->keyBy('id');

            if ($subjects->count() !== count($subjectIds)) {
                throw ValidationException::withMessages([
                    'items' => ['One or more selected subjects are invalid or inactive.'],
                ]);
            }

            $overwriteExisting = (bool) ($data['overwrite_existing'] ?? false);

            $created = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($items as $index => $item) {
                $subject = $subjects[(int) $item['subject_id']];

                $keys = [
                    'tenant_id' => $tenantId,
                    'curriculum_id' => $curriculum->id,
                    'academic_term_id' => $data['academic_term_id'] ?? null,
                    'subject_id' => $subject->id,
                ];

                $existing = CurriculumSubject::query()
                    ->where($keys)
                    ->first();

                if ($existing && !$overwriteExisting) {
                    $skipped++;
                    continue;
                }

                $payload = [
                    'program_id' => $data['program_id'],

                    'curriculum_subject_type' => 'regular',
                    'elective_group_code' => null,
                    'elective_group_name' => null,
                    'elective_required_count' => null,

                    'subject_code' => $item['subject_code'] ?: $subject->code,
                    'subject_name' => $item['subject_name'] ?: $subject->name,
                    'subject_nature' => $item['subject_nature'] ?: $subject->subject_nature,

                    'term_number' => $data['term_number'],

                    'credit_hours' => $item['credit_hours'] ?? $subject->credit_hours,
                    'theory_hours' => $item['theory_hours'] ?? $subject->theory_hours,
                    'practical_hours' => $item['practical_hours'] ?? $subject->practical_hours,
                    'tutorial_hours' => $item['tutorial_hours'] ?? $subject->tutorial_hours,

                    'total_marks' => $item['total_marks'] ?? $subject->total_marks,
                    'passing_marks' => $item['passing_marks'] ?? $subject->passing_marks,

                    'is_compulsory' => (bool) $item['is_compulsory'],
                    'is_credit_subject' => (bool) $item['is_credit_subject'],

                    'display_order' => $item['display_order'] ?? ($index + 1),
                    'remarks' => $item['remarks'] ?? null,

                    'status' => 'active',
                    'updated_by' => $user->id,
                ];

                if ($existing) {
                    $existing->update($payload);
                    $updated++;
                } else {
                    CurriculumSubject::create(array_merge($keys, $payload, [
                        'created_by' => $user->id,
                    ]));

                    $created++;
                }
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'total_selected' => $items->count(),
            ];
        });
    }
}