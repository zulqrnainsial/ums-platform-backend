<?php

use App\Core\Auth\Controllers\AuthController;
use App\Core\Dynamic\Controllers\DynamicCrudController;
use App\Core\Dynamic\Controllers\DynamicMetaController;
use App\Core\Dynamic\Controllers\DynamicOptionController;
use App\Core\Menu\Controllers\MenuController;
use App\Core\Modules\Controllers\ModuleController;
use App\Core\RBAC\Controllers\PermissionController;
use App\Core\RBAC\Controllers\RoleController;
use App\Core\RBAC\Controllers\UserRoleController;
use App\Core\Tenant\Controllers\TenantController;
use App\Core\User\Controllers\UserController;
use App\Helpers\ApiResponse;
use App\Modules\Admission\Controllers\ApplicantApplicationChecklistController;
use App\Modules\Admission\Controllers\ApplicantAuthController;
use App\Modules\Admission\Controllers\ApplicantDocumentController;
use App\Modules\Admission\Controllers\ApplicantEligibilityController;
use App\Modules\Admission\Controllers\ApplicantFeeVoucherController;
use App\Modules\Admission\Controllers\ApplicantPortalApplicationController;
use App\Modules\Admission\Controllers\ApplicantSelfServiceController;
use App\Modules\Subject\Controllers\CurriculumElectiveBulkController;
use App\Modules\Subject\Controllers\CurriculumSubjectBulkController;
use Illuminate\Support\Facades\Route;
use App\Modules\Admission\Controllers\EligibilityPolicyBuilderController;
use App\Modules\Admission\Controllers\ApplicantPreferenceGroupController;
use App\Modules\Admission\Controllers\ApplicantPaymentVerificationController;
use App\Modules\Admission\Controllers\ApplicantProgressController;
use App\Modules\Assessment\Controllers\QuestionEditorController;
use App\Modules\Assessment\Controllers\AssessmentBuilderController;
use App\Modules\Assessment\Controllers\AssessmentParticipantController;
use App\Modules\Assessment\Controllers\ApplicantAssessmentController;
use App\Modules\Assessment\Controllers\AssessmentResultController;
use App\Modules\Assessment\Controllers\AssessmentAdmissionSyncController;
use App\Modules\Assessment\Controllers\AssessmentAttemptAdminController;
use App\Modules\Assessment\Controllers\AssessmentResultAdminController;
use App\Modules\Assessment\Controllers\AssessmentAnalyticsController;
use App\Modules\Assessment\Controllers\AssessmentScheduleUtilityController;
use App\Modules\Assessment\Controllers\AssessmentManualMarkingController;
use App\Modules\Admission\Controllers\AdmissionMeritFormulaBuilderController;
use App\Modules\Admission\Controllers\AdmissionMeritCalculationController;
use App\Modules\Admission\Controllers\AdmissionApplicantMeritScoreAdminController;
use App\Modules\Admission\Controllers\AdmissionMeritListController;
use App\Modules\Admission\Controllers\AdmissionMeritOfferController;
use App\Modules\Admission\Controllers\ApplicantAdmissionOfferController;
use App\Http\Controllers\DynamicFieldStorageRuleController;
use App\Http\Controllers\DynamicFieldStorageValidationController;
use App\Modules\Admission\Controllers\AdmissionOfferVoucherController;
use App\Modules\Admission\Controllers\AdmissionConfirmationController;
use App\Modules\Admission\Controllers\AdmissionClosureReportController;
use App\Modules\Admission\Controllers\AdmissionWaitingListController;
use App\Modules\Admission\Controllers\AdmissionDashboardController;
use App\Modules\Admission\Controllers\AdmissionReportController;
use App\Modules\Admission\Controllers\AdmissionFinalizationController;
use App\Modules\Admission\Controllers\PublicAdmissionGuidanceController;
use App\Modules\Admission\Controllers\ApplicantEditLockController;
use App\Modules\Student\Controllers\StudentAcademicController;
use App\Modules\Student\Controllers\StudentPortalController;
use App\Modules\Student\Controllers\StudentRequestAdminController;
use App\Modules\Attendance\Controllers\AttendanceMarkingController;
use App\Modules\Attendance\Controllers\AttendanceSessionController;
use App\Modules\Attendance\Controllers\AttendanceReportController;
use App\Modules\FacultyAllocation\Controllers\FacultyAllocationController;
use App\Modules\ResourceManagement\Controllers\ResourceManagementController;

Route::get('/health', function () {
    return ApiResponse::success([
        'app' => config('app.name'),
        'status' => 'ok',
        'timestamp' => now()->toDateTimeString(),
    ], 'API is running.');
});

/*
|--------------------------------------------------------------------------
| Public Auth Routes
|--------------------------------------------------------------------------
*/

Route::post('/auth/login', [AuthController::class, 'login']);

Route::prefix('applicant/auth')->group(function () {
    Route::post('/register', [ApplicantAuthController::class, 'register']);
    Route::post('/login', [ApplicantAuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Protected Applicant Auth Routes
|--------------------------------------------------------------------------
| These routes are protected only by Sanctum. They intentionally do not use
| tenant.active because the applicant token already contains the tenant context.
*/
Route::get('/public/admission-guidance', [PublicAdmissionGuidanceController::class, 'index']);
Route::middleware('auth:sanctum')
    ->prefix('applicant/auth')
    ->group(function () {
        Route::get('/me', [ApplicantAuthController::class, 'me']);
        Route::post('/logout', [ApplicantAuthController::class, 'logout']);
    });

/*
|--------------------------------------------------------------------------
| Applicant Self-Service Routes
|--------------------------------------------------------------------------
| These routes never accept applicant_id from the frontend. The controller
| resolves the applicant from the authenticated Applicant user.
*/

Route::middleware('auth:sanctum')
    ->prefix('applicant/self-service')
    ->group(function () {
        Route::get('/profile', [ApplicantSelfServiceController::class, 'profile']);
        Route::get('/edit-locks', [ApplicantEditLockController::class, 'me']);
        Route::put('/profile', [ApplicantSelfServiceController::class, 'updateProfile']);

        Route::get('/qualifications', [ApplicantSelfServiceController::class, 'qualifications']);
        Route::post('/qualifications', [ApplicantSelfServiceController::class, 'saveQualification']);
        Route::delete('/qualifications/{id}', [ApplicantSelfServiceController::class, 'deleteQualification']);

        Route::get('/experiences', [ApplicantSelfServiceController::class, 'experiences']);
        Route::post('/experiences', [ApplicantSelfServiceController::class, 'saveExperience']);
        Route::delete('/experiences/{id}', [ApplicantSelfServiceController::class, 'deleteExperience']);

        Route::get('/research-profile', [ApplicantSelfServiceController::class, 'researchProfile']);
        Route::post('/research-profile', [ApplicantSelfServiceController::class, 'saveResearchProfile']);

        Route::get('/publications', [ApplicantSelfServiceController::class, 'publications']);
        Route::post('/publications', [ApplicantSelfServiceController::class, 'savePublication']);
        Route::delete('/publications/{id}', [ApplicantSelfServiceController::class, 'deletePublication']);

        Route::get('/test-results', [ApplicantSelfServiceController::class, 'testResults']);
        Route::post('/test-results', [ApplicantSelfServiceController::class, 'saveTestResult']);
        Route::delete('/test-results/{id}', [ApplicantSelfServiceController::class, 'deleteTestResult']);

        Route::get('/documents', [ApplicantSelfServiceController::class, 'documents']);

        Route::get('/eligible-programs', [ApplicantSelfServiceController::class, 'eligiblePrograms']);
        Route::get('/applications', [ApplicantSelfServiceController::class, 'applications']);
        Route::post('/applications/apply', [ApplicantSelfServiceController::class, 'apply']);

        Route::get('/applications/{applicationId}/checklist', [ApplicantSelfServiceController::class, 'checklist']);
        Route::post('/applications/{applicationId}/final-submit', [ApplicantSelfServiceController::class, 'finalSubmit']);

        Route::post('/applications/{applicationId}/voucher', [ApplicantSelfServiceController::class, 'generateVoucher']);
        Route::get('/applications/{applicationId}/vouchers', [ApplicantSelfServiceController::class, 'vouchers']);

        Route::post('/payments/submit', [ApplicantSelfServiceController::class, 'submitPayment']);

        Route::prefix('preference-group')->group(function () {
            Route::get('/', [ApplicantPreferenceGroupController::class, 'myShow']);
            Route::post('/preferences', [ApplicantPreferenceGroupController::class, 'myAddPreference']);
            Route::patch('/reorder', [ApplicantPreferenceGroupController::class, 'myReorder']);
            Route::delete('/preferences/{applicationId}', [ApplicantPreferenceGroupController::class, 'myRemove']);
            Route::post('/{groupId}/submit', [ApplicantPreferenceGroupController::class, 'mySubmit']);
        });
        Route::prefix('assessments')->group(function () {
            Route::get('/my-tests', [ApplicantAssessmentController::class, 'myTests']);
            Route::get('/participants/{participantId}/roll-no-slip', [ApplicantAssessmentController::class, 'rollNoSlip']);

            Route::post('/participants/{participantId}/start-attempt', [ApplicantAssessmentController::class, 'startAttempt']);
            Route::get('/attempts/{attemptId}', [ApplicantAssessmentController::class, 'getAttempt']);
            Route::post('/attempts/{attemptId}/answers', [ApplicantAssessmentController::class, 'saveAnswer']);
            Route::post('/attempts/{attemptId}/submit', [ApplicantAssessmentController::class, 'submitAttempt']);
            //Route::post('/applicant/self-service/assessments/attempts/{attemptId}/activity',[ApplicantAssessmentController::class, 'logActivity']);
            Route::post('/attempts/{attemptId}/activity', [ApplicantAssessmentController::class, 'logActivity']);
            Route::get('/attempts/{attemptId}/review', [ApplicantAssessmentController::class, 'attemptReview']);
        });
        
    });
Route::middleware('auth:sanctum')
    ->prefix('applicant/admission-offers')
    ->group(function () {
        Route::get('/', [ApplicantAdmissionOfferController::class, 'myOffers']);
        Route::post('/{meritListApplicantId}/accept', [ApplicantAdmissionOfferController::class, 'accept']);
        Route::post('/{meritListApplicantId}/reject', [ApplicantAdmissionOfferController::class, 'reject']);
        Route::post('/{meritListApplicantId}/submit-payment', [ApplicantAdmissionOfferController::class, 'submitPayment']);
    });
Route::middleware('auth:sanctum')
    ->prefix('admission/offer-vouchers')
    ->group(function () {
        Route::get('/', [AdmissionOfferVoucherController::class, 'index']);
        Route::post('/generate', [AdmissionOfferVoucherController::class, 'generate']);
        Route::post('/{voucherId}/mark-paid', [AdmissionOfferVoucherController::class, 'markPaid']);
        Route::post('/{voucherId}/verify-payment', [AdmissionOfferVoucherController::class, 'verifyPayment']);
        Route::post('/{voucherId}/reject-payment', [AdmissionOfferVoucherController::class, 'rejectPayment']);
    });

Route::middleware('auth:sanctum')
    ->prefix('admission/confirmations')
    ->group(function () {
        Route::get('/', [AdmissionConfirmationController::class, 'index']);
        Route::post('/confirm', [AdmissionConfirmationController::class, 'confirm']);
    });
Route::middleware('auth:sanctum')
    ->prefix('admission/closure-reports')
    ->group(function () {
        Route::get('/admitted-candidates', [AdmissionClosureReportController::class, 'admittedCandidates']);
        Route::get('/seat-summary', [AdmissionClosureReportController::class, 'seatSummary']);
    });
Route::middleware('auth:sanctum')
    ->post('/admission/confirmations/{confirmationId}/transfer-student', [AdmissionConfirmationController::class, 'transferConfirmedApplicant']);
Route::middleware('auth:sanctum')
    ->prefix('admission/dashboard')
    ->group(function () {
        Route::get('/summary', [AdmissionDashboardController::class, 'summary']);
    });

Route::middleware('auth:sanctum')
    ->prefix('admission/reports')
    ->group(function () {
        Route::get('/applicants', [AdmissionReportController::class, 'applicants']);
        Route::get('/applications', [AdmissionReportController::class, 'applications']);
        Route::get('/merit-scores', [AdmissionReportController::class, 'meritScores']);
        Route::get('/merit-lists', [AdmissionReportController::class, 'meritLists']);
        Route::get('/offers', [AdmissionReportController::class, 'offers']);
        Route::get('/vouchers', [AdmissionReportController::class, 'vouchers']);
        Route::get('/confirmed-admissions', [AdmissionReportController::class, 'confirmedAdmissions']);
        Route::get('/seat-summary', [AdmissionReportController::class, 'seatSummary']);
        Route::get('/waiting-list', [AdmissionReportController::class, 'waitingList']);
    });
Route::middleware('auth:sanctum')
    ->prefix('admission/waiting-lists')
    ->group(function () {
        Route::post('/merit-lists/{meritListId}/generate', [AdmissionWaitingListController::class, 'generate']);
        Route::post('/merit-lists/{meritListId}/promote-next', [AdmissionWaitingListController::class, 'promoteNext']);
        Route::get('/merit-lists/{meritListId}/movements', [AdmissionWaitingListController::class, 'movements']);
    });
/*
|--------------------------------------------------------------------------
| Tenant/Admin Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'tenant.active'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all-devices', [AuthController::class, 'logoutAllDevices']);
    Route::get('/auth/menus', [AuthController::class, 'menus']);

    /*
    |--------------------------------------------------------------------------
    | Menu Management
    |--------------------------------------------------------------------------
    */

    Route::prefix('menus')->group(function () {
        Route::get('/', [MenuController::class, 'index']);
        Route::post('/', [MenuController::class, 'store']);
        Route::get('/options', [MenuController::class, 'options']);
        Route::get('/tree', [MenuController::class, 'tree']);
        Route::get('/next-display-order', [MenuController::class, 'nextDisplayOrder']);
        Route::get('/{menu}', [MenuController::class, 'show']);
        Route::put('/{menu}', [MenuController::class, 'update']);
        Route::delete('/{menu}', [MenuController::class, 'destroy']);
        Route::post('/{menu}/activate', [MenuController::class, 'activate']);
        Route::post('/{menu}/deactivate', [MenuController::class, 'deactivate']);
    });
    Route::prefix('admission/applicant-portal/applicants/{applicantId}/preference-group')->group(function () {
        Route::get('/', [ApplicantPreferenceGroupController::class, 'adminShow']);
        Route::post('/preferences', [ApplicantPreferenceGroupController::class, 'adminAddPreference']);
        Route::patch('/reorder', [ApplicantPreferenceGroupController::class, 'adminReorder']);
        Route::delete('/preferences/{applicationId}', [ApplicantPreferenceGroupController::class, 'adminRemove']);
        Route::post('/{groupId}/submit', [ApplicantPreferenceGroupController::class, 'adminSubmit']);
    });
    Route::prefix('platform/dynamic-field-storage-rules')->group(function () {
        Route::get('/', [DynamicFieldStorageRuleController::class, 'index']);
        Route::get('/{moduleCode}/{entityKey}', [DynamicFieldStorageRuleController::class, 'entity']);
    });
    Route::get(
        '/platform/dynamic-field-storage-validation',
        [DynamicFieldStorageValidationController::class, 'index']
    );
    /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    */

    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::post('/{user}/activate', [UserController::class, 'activate']);
        Route::post('/{user}/deactivate', [UserController::class, 'deactivate']);
    });

    /*
    |--------------------------------------------------------------------------
    | Student Management APIs
    |--------------------------------------------------------------------------
    */    

    Route::prefix('student-management')->group(function () {
        Route::get('/students', [StudentAcademicController::class, 'students']);
        Route::get('/students/{studentId}', [StudentAcademicController::class, 'showStudent']);
        Route::patch('/students/{studentId}/status', [StudentAcademicController::class, 'updateStudentStatus']);
        Route::patch('/students/{studentId}/profile', [StudentAcademicController::class, 'updateStudentProfile']);
        Route::get('/students/{studentId}/profile-completion', [StudentAcademicController::class, 'profileCompletionSummary']);

        Route::get('/enrollments', [StudentAcademicController::class, 'enrollments']);
        Route::patch('/enrollments/{enrollmentId}', [StudentAcademicController::class, 'updateEnrollment']);
        Route::patch('/enrollments/{enrollmentId}/allocation', [StudentAcademicController::class, 'updateEnrollmentAllocation']);

        Route::get('/academic-placement-options', [StudentAcademicController::class, 'academicPlacementOptions']);

        Route::get('/students/{studentId}/course-registration/context', [StudentAcademicController::class, 'courseRegistrationContext']);
        Route::get('/students/{studentId}/course-registration/available-courses', [StudentAcademicController::class, 'availableCourses']);
        Route::get('/students/{studentId}/course-registration/registered-courses', [StudentAcademicController::class, 'registeredCourses']);
        Route::post('/students/{studentId}/course-registration/register', [StudentAcademicController::class, 'registerCourses']);
        Route::delete('/course-registration/{registrationId}', [StudentAcademicController::class, 'unregisterCourse']);

        Route::get('/section-batch-allocation/context', [StudentAcademicController::class, 'allocationContext']);
        Route::post('/section-batch-allocation/bulk-allocate', [StudentAcademicController::class, 'bulkAllocate']);

        Route::get('/lifecycle/context', [StudentAcademicController::class, 'lifecycleContext']);
        Route::post('/students/{studentId}/lifecycle-action', [StudentAcademicController::class, 'applyLifecycleAction']);
        Route::patch(
            '/student-documents/{documentId}/verify',
            [StudentAcademicController::class, 'verifyStudentDocument']
        );
        Route::prefix('student-requests')->group(function () {
            Route::get('/', [StudentRequestAdminController::class, 'index']);
            Route::get('/{requestId}', [StudentRequestAdminController::class, 'show']);
            Route::post('/{requestId}/decision', [StudentRequestAdminController::class, 'decide']);
        });
        Route::get('/bulk-course-registration/context', [StudentAcademicController::class, 'bulkCourseRegistrationContext']);
        Route::get('/bulk-course-registration/preview', [StudentAcademicController::class, 'previewBulkCourseRegistration']);
        Route::post('/bulk-course-registration/register', [StudentAcademicController::class, 'registerBulkCourses']);

        Route::get('/course-registration-settings', [StudentAcademicController::class, 'courseRegistrationSettings']);
        Route::post('/course-registration-settings', [StudentAcademicController::class, 'saveCourseRegistrationSettings']);
    });

    Route::prefix('student-portal')->group(function () {
        Route::get('/dashboard', [StudentPortalController::class, 'dashboard']);
        Route::get('/profile', [StudentPortalController::class, 'profile']);
        Route::get('/enrollment', [StudentPortalController::class, 'enrollment']);
        Route::get('/courses', [StudentPortalController::class, 'courses']);
        Route::get('/documents', [StudentPortalController::class, 'documents']);

        Route::get('/requests', [StudentPortalController::class, 'requests']);
        Route::post('/requests/profile-correction', [StudentPortalController::class, 'submitProfileCorrectionRequest']);
        Route::post('/requests/document-resubmission', [StudentPortalController::class, 'submitDocumentResubmissionRequest']);
        Route::post('/requests/course-add-drop', [StudentPortalController::class, 'submitCourseAddDropRequest']);

        Route::get('/available-courses', [StudentPortalController::class, 'availableCourses']);

        Route::post('/profile-picture', [StudentPortalController::class, 'uploadProfilePicture']);
        Route::get('/fee-status', [StudentPortalController::class, 'feeStatus']);
        Route::post('/documents/upload', [StudentPortalController::class, 'uploadDocument']);

        Route::get('/research-publications', [StudentPortalController::class, 'researchPublications']);
        Route::post('/research-publications', [StudentPortalController::class, 'storeResearchPublication']);
        Route::delete('/research-publications/{publicationId}', [StudentPortalController::class, 'deleteResearchPublication']);
        Route::get('/course-registration/settings', [StudentPortalController::class, 'courseRegistrationSettings']);
        Route::get('/course-registration/available-courses', [StudentPortalController::class, 'selfRegistrationAvailableCourses']);
        Route::post('/course-registration/submit', [StudentPortalController::class, 'submitSelfCourseRegistration']);
        Route::get('/attendance', [StudentPortalController::class, 'attendance']);
    });
    /*
    |--------------------------------------------------------------------------
    | Admission Admin / Testing APIs
    |--------------------------------------------------------------------------
    */
    Route::prefix('admission/eligibility-policy')->group(function () {
        Route::get('/lookups', [EligibilityPolicyBuilderController::class, 'lookups']);
        Route::get('/offered-programs/{offeredProgramId}', [EligibilityPolicyBuilderController::class, 'show']);
        Route::post('/offered-programs/{offeredProgramId}', [EligibilityPolicyBuilderController::class, 'save']);
    });
    Route::prefix('admission/applicant-documents')->group(function () {
        Route::post('/upload', [ApplicantDocumentController::class, 'upload']);
        Route::get('/{id}', [ApplicantDocumentController::class, 'show']);
        Route::get('/{id}/download', [ApplicantDocumentController::class, 'download']);
        Route::get('/{id}/preview', [ApplicantDocumentController::class, 'preview']);
        Route::patch('/{id}/verification', [ApplicantDocumentController::class, 'updateVerification']);
        Route::delete('/{id}', [ApplicantDocumentController::class, 'destroy']);
    });

    Route::prefix('admission/eligibility')->group(function () {
        Route::post('/evaluate-program', [ApplicantEligibilityController::class, 'evaluateProgram']);
        Route::get('/applicants/{applicantId}/eligible-programs', [ApplicantEligibilityController::class, 'eligiblePrograms']);
    });
    Route::prefix('admission/merit-builder')->group(function () {
        Route::get('/source-catalog', [AdmissionMeritFormulaBuilderController::class, 'sourceCatalog']);
        Route::get('/formulas', [AdmissionMeritFormulaBuilderController::class, 'index']);
        Route::get('/formulas/{formulaId}', [AdmissionMeritFormulaBuilderController::class, 'show']);
        
        Route::post('/formulas', [AdmissionMeritFormulaBuilderController::class, 'storeFormula']);
        Route::put('/formulas/{formulaId}', [AdmissionMeritFormulaBuilderController::class, 'updateFormula']);
        Route::delete('/formulas/{formulaId}', [AdmissionMeritFormulaBuilderController::class, 'deleteFormula']);

        Route::post('/formulas/{formulaId}/components', [AdmissionMeritFormulaBuilderController::class, 'storeComponent']);
        Route::put('/components/{componentId}', [AdmissionMeritFormulaBuilderController::class, 'updateComponent']);
        Route::delete('/components/{componentId}', [AdmissionMeritFormulaBuilderController::class, 'deleteComponent']);

        Route::post('/formulas/{formulaId}/applicabilities', [AdmissionMeritFormulaBuilderController::class, 'storeApplicability']);
        Route::put('/applicabilities/{applicabilityId}', [AdmissionMeritFormulaBuilderController::class, 'updateApplicability']);
        Route::delete('/applicabilities/{applicabilityId}', [AdmissionMeritFormulaBuilderController::class, 'deleteApplicability']);
    });
    Route::prefix('admission/applicant-portal')->group(function () {
        Route::get('/applicants/{applicantId}/eligible-programs', [ApplicantPortalApplicationController::class, 'eligiblePrograms']);
        Route::get('/applicants/{applicantId}/applications', [ApplicantPortalApplicationController::class, 'applications']);
        Route::post('/applications/apply', [ApplicantPortalApplicationController::class, 'apply']);
        Route::post('/applications/{applicationId}/submit', [ApplicantPortalApplicationController::class, 'submit']);
        Route::get('/applications/{applicationId}', [ApplicantPortalApplicationController::class, 'show']);
    });

    Route::prefix('admission/applicant-portal/applications')->group(function () {
        Route::get('/{applicationId}/checklist', [ApplicantApplicationChecklistController::class, 'checklist']);
        Route::post('/{applicationId}/validate-final-submission', [ApplicantApplicationChecklistController::class, 'validateFinalSubmission']);
        Route::post('/{applicationId}/final-submit', [ApplicantApplicationChecklistController::class, 'finalSubmit']);
    });

    Route::prefix('admission/fees')->group(function () {
        Route::post('/vouchers/generate', [ApplicantFeeVoucherController::class, 'generate']);
        Route::get('/applications/{applicationId}/vouchers', [ApplicantFeeVoucherController::class, 'vouchersForApplication']);
        Route::post('/payments/submit', [ApplicantFeeVoucherController::class, 'submitPayment']);
        Route::patch('/payments/{paymentId}/verify', [ApplicantFeeVoucherController::class, 'verifyPayment']);
        Route::patch('/payments/{paymentId}/reject', [ApplicantFeeVoucherController::class, 'rejectPayment']);
    });
    Route::prefix('admission/payment-verification')->group(function () {
        Route::get('/payments', [ApplicantPaymentVerificationController::class, 'index']);
        Route::get('/payments/{paymentId}', [ApplicantPaymentVerificationController::class, 'show']);
        Route::patch('/payments/{paymentId}/verify', [ApplicantPaymentVerificationController::class, 'verify']);
        Route::patch('/payments/{paymentId}/reject', [ApplicantPaymentVerificationController::class, 'reject']);
    });
    Route::prefix('admission/applicant-progress')->group(function () {
        Route::get('/', [ApplicantProgressController::class, 'index']);
        Route::get('/{applicantId}', [ApplicantProgressController::class, 'show']);
    });
    Route::prefix('admission/merit-scores')->group(function () {
        Route::get('/', [AdmissionApplicantMeritScoreAdminController::class, 'index']);
        Route::get('/{scoreId}', [AdmissionApplicantMeritScoreAdminController::class, 'detail']);
    });
    Route::prefix('admission/merit-offers')->group(function () {
        Route::post('/lists/{meritListId}/generate-offers', [AdmissionMeritOfferController::class, 'generateOffers']);
        Route::get('/lists/{meritListId}/movements', [AdmissionMeritOfferController::class, 'movements']);

        Route::post('/applicants/{meritListApplicantId}/accept', [AdmissionMeritOfferController::class, 'accept']);
        Route::post('/applicants/{meritListApplicantId}/reject', [AdmissionMeritOfferController::class, 'reject']);
        Route::post('/applicants/{meritListApplicantId}/expire', [AdmissionMeritOfferController::class, 'expire']);
    });
    Route::prefix('assessment/questions')->group(function () {
        Route::get('/', [QuestionEditorController::class, 'index']);
        Route::get('/quality-dashboard', [QuestionEditorController::class, 'qualityDashboard']);

        Route::post('/', [QuestionEditorController::class, 'store']);
        Route::post('/bulk-import', [QuestionEditorController::class, 'bulkImport']);
        Route::post('/import-excel', [QuestionEditorController::class, 'importExcel']);

        Route::get('/{id}', [QuestionEditorController::class, 'show']);
        Route::put('/{id}', [QuestionEditorController::class, 'update']);
        Route::delete('/{id}', [QuestionEditorController::class, 'destroy']);
    });
    Route::prefix('admission/merit-lists')->group(function () {
        Route::get('/', [AdmissionMeritListController::class, 'index']);
        Route::post('/generate', [AdmissionMeritListController::class, 'generate']);
        Route::get('/{meritListId}', [AdmissionMeritListController::class, 'show']);
        Route::post('/{meritListId}/publish', [AdmissionMeritListController::class, 'publish']);
        Route::post('/{meritListId}/cancel', [AdmissionMeritListController::class, 'cancel']);
    });
    Route::prefix('assessment/builder')->group(function () {
        Route::get('/assessments/{assessmentId}', [AssessmentBuilderController::class, 'show']);

        Route::post('/assessments/{assessmentId}/sections', [AssessmentBuilderController::class, 'createSection']);
        Route::put('/sections/{sectionId}', [AssessmentBuilderController::class, 'updateSection']);
        Route::delete('/sections/{sectionId}', [AssessmentBuilderController::class, 'deleteSection']);

        Route::get('/questions/available', [AssessmentBuilderController::class, 'availableQuestions']);
        Route::post('/sections/{sectionId}/bulk-assign-questions', [AssessmentBuilderController::class, 'bulkAssignQuestions']);

        Route::delete('/assessment-questions/{assessmentQuestionId}', [AssessmentBuilderController::class, 'removeAssessmentQuestion']);
    });
    Route::prefix('assessment/participants')->group(function () {
        Route::get('/', [AssessmentParticipantController::class, 'index']);
        Route::get('/candidates/applicants', [AssessmentParticipantController::class, 'candidates']);
        Route::post('/bulk-assign-applicants', [AssessmentParticipantController::class, 'bulkAssignApplicants']);
        Route::post('/generate-roll-numbers', [AssessmentParticipantController::class, 'generateRollNumbers']);
        Route::get('/{participantId}/roll-no-slip', [AssessmentParticipantController::class, 'rollNoSlip']);
    });   
    Route::prefix('assessment/results')->group(function () {
        Route::post('/attempts/{attemptId}/generate', [AssessmentResultController::class, 'generateForAttempt']);
        Route::post('/{resultId}/approve', [AssessmentResultController::class, 'approve']);
        Route::post('/{resultId}/publish', [AssessmentResultController::class, 'publish']);
    });
    Route::prefix('assessment/admission-sync')->group(function () {
        Route::post('/results/{assessmentResultId}', [AssessmentAdmissionSyncController::class, 'syncResult']);
    });
    Route::prefix('assessment/admin/attempts')->group(function () {
        Route::get('/', [AssessmentAttemptAdminController::class, 'index']);
        Route::get('/{attemptId}', [AssessmentAttemptAdminController::class, 'detail']);
        Route::get(
        '/assessment/admin/attempts/{attemptId}/activity-logs',
        [AssessmentAttemptAdminController::class, 'activityLogs']
    );
    });
    Route::prefix('assessment/admin/results')->group(function () {
        Route::get('/', [AssessmentResultAdminController::class, 'index']);
        Route::get('/{resultId}', [AssessmentResultAdminController::class, 'detail']);
        Route::post('/{resultId}/approve', [AssessmentResultAdminController::class, 'approve']);
        Route::post('/{resultId}/publish', [AssessmentResultAdminController::class, 'publish']);
        Route::post('/{resultId}/sync-to-admission', [AssessmentResultAdminController::class, 'syncToAdmission']);
    });
    Route::get('/assessment/admin/analytics', [AssessmentAnalyticsController::class, 'dashboard']);
    Route::post('/assessment/admin/schedules/preview', [AssessmentScheduleUtilityController::class, 'preview']);
    Route::prefix('assessment/admin/manual-marking')->group(function () {
        Route::get('/pending', [AssessmentManualMarkingController::class, 'pending']);
        Route::post('/answers/{answerId}/mark', [AssessmentManualMarkingController::class, 'mark']);
    });
    Route::prefix('admission/merit-calculation')->group(function () {
        Route::post('/calculate-applicant', [AdmissionMeritCalculationController::class, 'calculateForApplicant']);
        Route::post('/bulk-calculate', [AdmissionMeritCalculationController::class, 'bulkCalculate']);
    });
    Route::prefix('admission/closure-reports')->group(function () {
        Route::get('/admitted-candidates', [AdmissionClosureReportController::class, 'admittedCandidates']);
        Route::get('/seat-summary', [AdmissionClosureReportController::class, 'seatSummary']);
    });

    Route::post(
        '/admission/confirmations/{confirmationId}/transfer-student',
        [AdmissionConfirmationController::class, 'transferConfirmedApplicant']
    );
    Route::post(
        '/admission/confirmations/{confirmationId}/finalize',
        [AdmissionFinalizationController::class, 'finalize']
    );
    Route::get('/applicant/edit-locks/me', [ApplicantEditLockController::class, 'me']);
    Route::get('/admission/applicants/{applicantId}/edit-locks', [ApplicantEditLockController::class, 'show']);
    Route::get(
        '/admission/applicants/{applicantId}/edit-locks',
        [ApplicantEditLockController::class, 'show']
    );
    Route::post(
        '/assessment/questions/suggest-metadata',
        [QuestionEditorController::class, 'suggestMetadata']
    );
    /*
    |--------------------------------------------------------------------------
    | Dynamic Options
    |--------------------------------------------------------------------------
    */

    Route::prefix('dynamic-options')->group(function () {
        Route::get('/campuses', [DynamicOptionController::class, 'campuses']);
        Route::get('/buildings', [DynamicOptionController::class, 'buildings']);
        Route::get('/floors', [DynamicOptionController::class, 'floors']);
        Route::get('/faculties', [DynamicOptionController::class, 'faculties']);
        Route::get('/institutes', [DynamicOptionController::class, 'institutes']);
        Route::get('/departments', [DynamicOptionController::class, 'departments']);
        Route::get('/academic-placement-options', [StudentAcademicController::class, 'academicPlacementOptions']);
        Route::get('/academic-sessions', [DynamicOptionController::class, 'academicSessions']);
        Route::get('/program-levels', [DynamicOptionController::class, 'programLevels']);
        Route::get('/programs', [DynamicOptionController::class, 'programs']);
        Route::get('/academic-terms', [DynamicOptionController::class, 'academicTerms']);
        Route::get('/subject-types', [DynamicOptionController::class, 'subjectTypes']);
        Route::get('/subject-groups', [DynamicOptionController::class, 'subjectGroups']);
        Route::get('/subjects', [DynamicOptionController::class, 'subjects']);
        Route::get('/curriculums', [DynamicOptionController::class, 'curriculums']);
        Route::get('/lookups/{categoryCode}', [DynamicOptionController::class, 'lookups']);
        Route::get('/lookup-categories', [DynamicOptionController::class, 'lookupCategories']);
        Route::get('/lookup-values', [DynamicOptionController::class, 'lookupValues']);
        Route::get('/students', [DynamicOptionController::class, 'students']);
        Route::get('/guardians', [DynamicOptionController::class, 'guardians']);
        Route::get('/student-batches', [DynamicOptionController::class, 'studentBatches']);
        Route::get('/admission-sessions', [DynamicOptionController::class, 'admissionSessions']);
        Route::get('/offered-programs', [DynamicOptionController::class, 'offeredPrograms']);
        Route::get('/program-quota-seats', [DynamicOptionController::class, 'programQuotaSeats']);
        Route::get('/eligibility-rule-types', [DynamicOptionController::class, 'eligibilityRuleTypes']);
        Route::get('/applicants', [DynamicOptionController::class, 'applicants']);
        Route::get('/applicant-qualifications', [DynamicOptionController::class, 'applicantQualifications']);
        Route::get('/applicant-program-applications', [DynamicOptionController::class, 'applicantProgramApplications']);
        Route::get('/applicant-documents', [DynamicOptionController::class, 'applicantDocuments']);
        Route::get('/admission-preference-groups', [DynamicOptionController::class, 'admissionPreferenceGroups']);
        Route::get('/admission-preference-group-programs', [DynamicOptionController::class, 'admissionPreferenceGroupPrograms']);
        Route::get('/assessment-categories', [DynamicOptionController::class, 'assessmentCategories']);
        Route::get('/assessment-subjects', [DynamicOptionController::class, 'assessmentSubjects']);
        Route::get('/assessment-topics', [DynamicOptionController::class, 'assessmentTopics']);
        Route::get('/question-banks', [DynamicOptionController::class, 'questionBanks']);
        Route::get('/questions', [DynamicOptionController::class, 'questions']);
        Route::get('/assessments', [DynamicOptionController::class, 'assessments']);
        Route::get('/assessment-sections', [DynamicOptionController::class, 'assessmentSections']);
        Route::get('/assessment-questions', [DynamicOptionController::class, 'assessmentQuestions']);
        Route::get('/assessment-schedules', [DynamicOptionController::class, 'assessmentSchedules']);
        Route::get('/assessment-participants', [DynamicOptionController::class, 'assessmentParticipants']);
        Route::get('/admission-merit-formulas', [DynamicOptionController::class, 'admissionMeritFormulas']);

    });

    /*
    |--------------------------------------------------------------------------
    | Subject Special Operations
    |--------------------------------------------------------------------------
    */

    Route::prefix('subject/curriculum-subjects')->group(function () {
        Route::post('/bulk-assign', [CurriculumSubjectBulkController::class, 'assign']);
    });

    Route::prefix('subject/curriculum-electives')->group(function () {
        Route::post('/bulk-assign', [CurriculumElectiveBulkController::class, 'assign']);
    });

    /*
    |--------------------------------------------------------------------------
    | Dynamic CRUD Engine
    |--------------------------------------------------------------------------
    */

    Route::prefix('dynamic')->group(function () {
        Route::get('/meta/{entityCode}', [DynamicMetaController::class, 'show']);
        Route::get('/crud/{entityCode}', [DynamicCrudController::class, 'index']);
        Route::post('/crud/{entityCode}', [DynamicCrudController::class, 'store']);
        Route::get('/crud/{entityCode}/{id}', [DynamicCrudController::class, 'show']);
        Route::put('/crud/{entityCode}/{id}', [DynamicCrudController::class, 'update']);
        Route::delete('/crud/{entityCode}/{id}', [DynamicCrudController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Tenant Management
    |--------------------------------------------------------------------------
    */

    Route::prefix('admin')->group(function () {
        Route::get('/tenants/options', [TenantController::class, 'options']);
        Route::post('/tenants/{tenant}/activate', [TenantController::class, 'activate']);
        Route::post('/tenants/{tenant}/deactivate', [TenantController::class, 'deactivate']);
        Route::post('/tenants/{tenant}/suspend', [TenantController::class, 'suspend']);
        Route::post('/tenants/{tenant}/assign-modules', [TenantController::class, 'assignModules']);
        Route::apiResource('tenants', TenantController::class);
    });

    /*
    |--------------------------------------------------------------------------
    | Module Management
    |--------------------------------------------------------------------------
    */

    Route::prefix('modules')->group(function () {
        Route::get('/options', [ModuleController::class, 'options']);
        Route::post('/{module}/activate', [ModuleController::class, 'activate']);
        Route::post('/{module}/deactivate', [ModuleController::class, 'deactivate']);

        Route::apiResource('/', ModuleController::class)
            ->parameters(['' => 'module']);
    });

    Route::prefix('admin/tenants/{tenant}/modules')->group(function () {
        Route::get('/', [ModuleController::class, 'tenantModules']);
        Route::get('/assignment-options', [ModuleController::class, 'tenantModuleAssignmentOptions']);
        Route::post('/assign', [ModuleController::class, 'assignTenantModules']);
        Route::post('/{module}/enable', [ModuleController::class, 'enableTenantModule']);
        Route::post('/{module}/disable', [ModuleController::class, 'disableTenantModule']);
    });
    /*
    |--------------------------------------------------------------------------
    | Attendance Management
    |--------------------------------------------------------------------------
    */

    Route::prefix('attendance')->group(function () {
        Route::get('/sessions', [AttendanceSessionController::class, 'index']);
        Route::get('/sessions/{session}', [AttendanceSessionController::class, 'show']);
        Route::delete('/sessions/{session}', [AttendanceSessionController::class, 'destroy']);

        Route::get('/marking/context', [AttendanceMarkingController::class, 'context']);
        Route::get('/marking/students', [AttendanceMarkingController::class, 'students']);
        Route::post('/marking/save', [AttendanceMarkingController::class, 'save']);
        Route::post('/sessions/{session}/lock', [AttendanceMarkingController::class, 'lock']);

        Route::get('/reports/summary', [AttendanceReportController::class, 'summary']);
        Route::get('/reports/student-course-percentages', [AttendanceReportController::class, 'studentCoursePercentages']);
        Route::get('/reports/defaulters', [AttendanceReportController::class, 'defaulters']);
    });

    
    /*
    |--------------------------------------------------------------------------
    | RBAC
    |--------------------------------------------------------------------------
    */

    Route::prefix('rbac')->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index']);
        Route::get('/permissions/grouped', [PermissionController::class, 'grouped']);
        Route::post('/permissions/generate', [PermissionController::class, 'generate']);
        Route::get('/roles/options', [RoleController::class, 'options']);
        Route::post('/roles/{role}/assign-permissions', [RoleController::class, 'assignPermissions']);
        Route::apiResource('roles', RoleController::class);
        Route::post('/users/{user}/assign-roles', [UserRoleController::class, 'assignRoles']);
    });

    /*
    |--------------------------------------------------------------------------
    | Faculty Allocation
    |--------------------------------------------------------------------------
    */
    Route::prefix('faculty-allocation')->group(function () {
        Route::get('/context', [FacultyAllocationController::class, 'context']);

        Route::get('/faculty-members', [FacultyAllocationController::class, 'facultyMembers']);
        Route::post('/faculty-members', [FacultyAllocationController::class, 'storeFacultyMember']);
        Route::put('/faculty-members/{facultyMember}', [FacultyAllocationController::class, 'updateFacultyMember']);

        Route::get('/load-policies', [FacultyAllocationController::class, 'loadPolicies']);
        Route::post('/load-policies', [FacultyAllocationController::class, 'storeLoadPolicy']);

        Route::get('/faculty-members/{facultyMember}/availability', [FacultyAllocationController::class, 'availability']);
        Route::post('/faculty-members/{facultyMember}/availability', [FacultyAllocationController::class, 'storeAvailability']);

        Route::get('/faculty-members/{facultyMember}/subject-expertise', [FacultyAllocationController::class, 'subjectExpertise']);
        Route::post('/faculty-members/{facultyMember}/subject-expertise', [FacultyAllocationController::class, 'storeSubjectExpertise']);

        Route::get('/course-offerings', [FacultyAllocationController::class, 'courseOfferings']);
        Route::post('/course-offerings', [FacultyAllocationController::class, 'storeCourseOffering']);
        Route::put('/course-offerings/{courseOffering}', [FacultyAllocationController::class, 'updateCourseOffering']);
        Route::post('/course-offerings/create-split', [FacultyAllocationController::class, 'createSplitCourseOfferings']);

        Route::get('/allocations', [FacultyAllocationController::class, 'allocations']);
        Route::post('/allocations/validate', [FacultyAllocationController::class, 'validateAllocation']);
        Route::post('/allocations', [FacultyAllocationController::class, 'storeAllocation']);

        Route::get('/conflicts', [FacultyAllocationController::class, 'conflicts']);

        Route::get('/teaching-groups', [FacultyAllocationController::class, 'teachingGroups']);
        Route::post('/teaching-groups', [FacultyAllocationController::class, 'storeTeachingGroup']);
        Route::put('/teaching-groups/{teachingGroup}', [FacultyAllocationController::class, 'updateTeachingGroup']);

        Route::get('/teaching-groups/{teachingGroup}/members', [FacultyAllocationController::class, 'teachingGroupMembers']);
        Route::post('/teaching-groups/{teachingGroup}/members/sync', [FacultyAllocationController::class, 'syncTeachingGroupMembers']);

        Route::get('/eligible-students', [FacultyAllocationController::class, 'eligibleStudentsForTeachingGroup']);
        Route::post('/teaching-groups/create-practical-from-section', [FacultyAllocationController::class, 'createPracticalGroupsFromSection']);

    });
    /*
    |--------------------------------------------------------------------------
    | Dashboard Summary
    |--------------------------------------------------------------------------
    */
    Route::prefix('resource-management')->group(function () {
        Route::get('/context', [ResourceManagementController::class, 'context']);

        Route::get('/buildings', [ResourceManagementController::class, 'buildings']);
        Route::post('/buildings', [ResourceManagementController::class, 'storeBuilding']);

        Route::get('/floors', [ResourceManagementController::class, 'floors']);
        Route::post('/floors', [ResourceManagementController::class, 'storeFloor']);

        Route::get('/rooms', [ResourceManagementController::class, 'rooms']);
        Route::post('/rooms', [ResourceManagementController::class, 'storeRoom']);

        Route::get('/rooms/available', [ResourceManagementController::class, 'availableRooms']);
    });

    /*
    |--------------------------------------------------------------------------
    | Dashboard Summary
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard/summary', function (\Illuminate\Http\Request $request) {
        $user = $request->user();

        $isSuperAdmin = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
        $tenantId = $user?->tenant_id;

        if ($isSuperAdmin) {
            return response()->json([
                'data' => [
                    'scope' => 'platform',
                    'tenants' => \Illuminate\Support\Facades\Schema::hasTable('tenants')
                        ? \Illuminate\Support\Facades\DB::table('tenants')->count()
                        : 0,
                    'active_tenants' => \Illuminate\Support\Facades\Schema::hasTable('tenants')
                        ? \Illuminate\Support\Facades\DB::table('tenants')->where('status', 'active')->count()
                        : 0,
                    'users' => \Illuminate\Support\Facades\Schema::hasTable('users')
                        ? \Illuminate\Support\Facades\DB::table('users')->count()
                        : 0,
                    'modules' => \Illuminate\Support\Facades\Schema::hasTable('modules')
                        ? \Illuminate\Support\Facades\DB::table('modules')->count()
                        : 0,
                    'login_logs' => \Illuminate\Support\Facades\Schema::hasTable('login_logs')
                        ? \Illuminate\Support\Facades\DB::table('login_logs')->count()
                        : 0,
                    'audit_logs' => \Illuminate\Support\Facades\Schema::hasTable('audit_logs')
                        ? \Illuminate\Support\Facades\DB::table('audit_logs')->count()
                        : 0,
                ],
                'message' => 'Dashboard summary fetched successfully.',
            ]);
        }

        return response()->json([
            'data' => [
                'scope' => 'tenant',
                'students' => \Illuminate\Support\Facades\Schema::hasTable('students')
                    ? \Illuminate\Support\Facades\DB::table('students')->where('tenant_id', $tenantId)->count()
                    : 0,
                'applicants' => \Illuminate\Support\Facades\Schema::hasTable('applicants')
                    ? \Illuminate\Support\Facades\DB::table('applicants')->where('tenant_id', $tenantId)->count()
                    : 0,
                'programs' => \Illuminate\Support\Facades\Schema::hasTable('programs')
                    ? \Illuminate\Support\Facades\DB::table('programs')->where('tenant_id', $tenantId)->count()
                    : 0,
                'enrollments' => \Illuminate\Support\Facades\Schema::hasTable('student_enrollments')
                    ? \Illuminate\Support\Facades\DB::table('student_enrollments')->where('tenant_id', $tenantId)->count()
                    : 0,
                'course_registrations' => \Illuminate\Support\Facades\Schema::hasTable('student_course_registrations')
                    ? \Illuminate\Support\Facades\DB::table('student_course_registrations')->where('tenant_id', $tenantId)->count()
                    : 0,
                'users' => \Illuminate\Support\Facades\Schema::hasTable('users')
                    ? \Illuminate\Support\Facades\DB::table('users')->where('tenant_id', $tenantId)->count()
                    : 0,
            ],
            'message' => 'Dashboard summary fetched successfully.',
        ]);
    });
});
