<?php

namespace App\Modules\Timetable\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Timetable\Services\TimetableSlotSetupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimetableSlotSetupController extends Controller
{
    public function context(TimetableSlotSetupService $service): JsonResponse { return response()->json(['data' => $service->context()]); }
    public function slots(int $slotSet, TimetableSlotSetupService $service): JsonResponse { return response()->json(['data' => $service->slots($slotSet)]); }

    public function storeSlotSet(Request $request, TimetableSlotSetupService $service): JsonResponse
    {
        $data = $request->validate([
            'slot_set_code' => ['required','string','max:50'], 'slot_set_name' => ['required','string','max:150'],
            'description' => ['nullable','string','max:500'], 'is_default' => ['nullable','boolean'],
            'status_code' => ['nullable','string','max:50'],
        ]);
        return response()->json(['data' => $service->createSlotSet($data), 'message' => 'Slot set created.'], 201);
    }

    public function storePeriod(Request $request, TimetableSlotSetupService $service): JsonResponse
    {
        $data = $request->validate([
            'academic_session_id' => ['required','integer'], 'academic_term_id' => ['required','integer'],
            'timetable_slot_set_id' => ['required','integer'], 'period_code' => ['required','string','max:50'],
            'period_name' => ['required','string','max:150'], 'start_date' => ['nullable','date'], 'end_date' => ['nullable','date','after_or_equal:start_date'],
            'priority' => ['nullable','integer','min:1'], 'is_default' => ['nullable','boolean'], 'status_code' => ['nullable','string','max:50'],
        ]);
        return response()->json(['data' => $service->createCalendarPeriod($data), 'message' => 'Calendar period created.'], 201);
    }

    public function storeSlot(Request $request, TimetableSlotSetupService $service): JsonResponse
    {
        $data = $request->validate([
            'timetable_slot_set_id' => ['required','integer'], 'day_of_week' => ['required','integer','between:1,7'],
            'slot_code' => ['required','string','max:50'], 'slot_name' => ['nullable','string','max:150'],
            'start_time' => ['required','date_format:H:i'], 'end_time' => ['required','date_format:H:i'], 'sort_order' => ['required','integer','min:1'],
            'is_teaching_slot' => ['nullable','boolean'], 'is_break' => ['nullable','boolean'], 'status_code' => ['nullable','string','max:50'],
        ]);
        return response()->json(['data' => $service->createSlot($data), 'message' => 'Timetable slot created.'], 201);
    }

    public function copyDay(Request $request, int $slotSet, TimetableSlotSetupService $service): JsonResponse
    {
        $data = $request->validate(['from_day_of_week' => ['required','integer','between:1,7'], 'to_day_of_week' => ['required','integer','between:1,7']]);
        return response()->json(['data' => ['copied_slots' => $service->copyDay($slotSet, $data['from_day_of_week'], $data['to_day_of_week'])], 'message' => 'Day slots copied.']);
    }
}
