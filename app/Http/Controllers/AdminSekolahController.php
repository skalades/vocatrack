<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AdminSekolah\AcademicController;
use App\Http\Controllers\AdminSekolah\BillingController;
use App\Http\Controllers\AdminSekolah\CertificateController;
use App\Http\Controllers\AdminSekolah\CompanyController;
use App\Http\Controllers\AdminSekolah\DashboardController;
use App\Http\Controllers\AdminSekolah\HolidayController;
use App\Http\Controllers\AdminSekolah\IncidentController;
use App\Http\Controllers\AdminSekolah\PlottingController;
use App\Http\Controllers\AdminSekolah\SettingController;
use App\Http\Controllers\AdminSekolah\StudentController;
use App\Http\Controllers\AdminSekolah\TeacherController;
use App\Http\Requests\AdminSekolah\StoreClassRequest;
use App\Http\Requests\AdminSekolah\StoreCompanyRequest;
use App\Http\Requests\AdminSekolah\StoreHolidayRequest;
use App\Http\Requests\AdminSekolah\StoreMajorRequest;
use App\Http\Requests\AdminSekolah\StorePlottingRequest;
use App\Http\Requests\AdminSekolah\StoreStudentRequest;
use App\Http\Requests\AdminSekolah\StoreTeacherRequest;
use App\Http\Requests\AdminSekolah\UpdateCompanyRequest;
use App\Http\Requests\AdminSekolah\UpdateSettingsRequest;
use App\Http\Requests\AdminSekolah\UpdateStudentRequest;
use App\Http\Requests\AdminSekolah\UpdateTeacherRequest;
use App\Models\Certificate;
use App\Models\Company;
use App\Models\IncidentReport;
use App\Models\Invoice;
use App\Models\LearningObjective;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\SchoolClass;
use App\Models\SchoolHoliday;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Services\CertificateService;
use App\Services\ClassPromotionService;
use App\Services\HolidayService;
use App\Services\IncidentReportService;
use App\Services\WhatsAppGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * AdminSekolahController
 *
 * Facade and backwards-compatible delegate for domain controllers under App\Http\Controllers\AdminSekolah.
 */
class AdminSekolahController extends Controller
{
    protected function getSchoolId(): int
    {
        return Auth::user()->school_id;
    }

    // --- Dashboard ---
    public function dashboard()
    {
        return app(DashboardController::class)->dashboard();
    }

    // --- Settings & Test Mode ---
    public function settings()
    {
        return app(SettingController::class)->settings();
    }

    public function updateSettings(UpdateSettingsRequest $request)
    {
        return app(SettingController::class)->updateSettings($request);
    }

    public function toggleTestMode(Request $request)
    {
        return app(SettingController::class)->toggleTestMode($request);
    }

    public function getTestModeStats(Request $request)
    {
        return app(SettingController::class)->getTestModeStats($request);
    }

    public function cleanTestData(Request $request)
    {
        return app(SettingController::class)->cleanTestData($request);
    }

    // --- Academic (Majors, Classes, Competencies, Promotion) ---
    public function majors()
    {
        return app(AcademicController::class)->majors();
    }

    public function storeMajor(StoreMajorRequest $request)
    {
        return app(AcademicController::class)->storeMajor($request);
    }

    public function updateMajor(Request $request, Major $major)
    {
        return app(AcademicController::class)->updateMajor($request, $major);
    }

    public function destroyMajor(Major $major)
    {
        return app(AcademicController::class)->destroyMajor($major);
    }

    public function storeMajorCompetency(Request $request, Major $major)
    {
        return app(AcademicController::class)->storeMajorCompetency($request, $major);
    }

    public function updateMajorCompetency(Request $request, LearningObjective $learningObjective)
    {
        return app(AcademicController::class)->updateMajorCompetency($request, $learningObjective);
    }

    public function destroyMajorCompetency(LearningObjective $learningObjective)
    {
        return app(AcademicController::class)->destroyMajorCompetency($learningObjective);
    }

    public function storeClass(StoreClassRequest $request)
    {
        return app(AcademicController::class)->storeClass($request);
    }

    public function updateClass(Request $request, SchoolClass $schoolClass)
    {
        return app(AcademicController::class)->updateClass($request, $schoolClass);
    }

    public function destroyClass(SchoolClass $schoolClass)
    {
        return app(AcademicController::class)->destroyClass($schoolClass);
    }

    public function previewPromotion(Request $request, ClassPromotionService $promotionService)
    {
        return app(AcademicController::class)->previewPromotion($request, $promotionService);
    }

    public function promoteClasses(Request $request, ClassPromotionService $promotionService)
    {
        return app(AcademicController::class)->promoteClasses($request, $promotionService);
    }

    // --- Students ---
    public function students(Request $request)
    {
        return app(StudentController::class)->students($request);
    }

    public function exportStudents(Request $request)
    {
        return app(StudentController::class)->exportStudents($request);
    }

    public function storeStudent(StoreStudentRequest $request)
    {
        return app(StudentController::class)->storeStudent($request);
    }

    public function updateStudent(UpdateStudentRequest $request, Student $student)
    {
        return app(StudentController::class)->updateStudent($request, $student);
    }

    public function resetStudentPassword(Request $request, Student $student)
    {
        return app(StudentController::class)->resetStudentPassword($request, $student);
    }

    public function destroyStudent(Student $student)
    {
        return app(StudentController::class)->destroyStudent($student);
    }

    public function importStudents(Request $request)
    {
        return app(StudentController::class)->importStudents($request);
    }

    // --- Teachers ---
    public function teachers(Request $request)
    {
        return app(TeacherController::class)->teachers($request);
    }

    public function exportTeachers(Request $request)
    {
        return app(TeacherController::class)->exportTeachers($request);
    }

    public function storeTeacher(StoreTeacherRequest $request)
    {
        return app(TeacherController::class)->storeTeacher($request);
    }

    public function updateTeacher(UpdateTeacherRequest $request, Teacher $teacher)
    {
        return app(TeacherController::class)->updateTeacher($request, $teacher);
    }

    public function resetTeacherPassword(Request $request, Teacher $teacher)
    {
        return app(TeacherController::class)->resetTeacherPassword($request, $teacher);
    }

    public function destroyTeacher(Teacher $teacher)
    {
        return app(TeacherController::class)->destroyTeacher($teacher);
    }

    // --- Companies & Supervisors ---
    public function companies(Request $request)
    {
        return app(CompanyController::class)->companies($request);
    }

    public function storeCompany(StoreCompanyRequest $request)
    {
        return app(CompanyController::class)->storeCompany($request);
    }

    public function updateCompany(UpdateCompanyRequest $request, Company $company)
    {
        return app(CompanyController::class)->updateCompany($request, $company);
    }

    public function destroyCompany(Company $company)
    {
        return app(CompanyController::class)->destroyCompany($company);
    }

    public function storeCompanySupervisor(Request $request, Company $company)
    {
        return app(CompanyController::class)->storeCompanySupervisor($request, $company);
    }

    public function updateCompanySupervisor(Request $request, Company $company, Supervisor $supervisor)
    {
        return app(CompanyController::class)->updateCompanySupervisor($request, $company, $supervisor);
    }

    public function destroyCompanySupervisor(Request $request, Company $company, Supervisor $supervisor)
    {
        return app(CompanyController::class)->destroyCompanySupervisor($request, $company, $supervisor);
    }

    public function reassignCompanyBatch(Request $request, Company $company)
    {
        return app(CompanyController::class)->reassignCompanyBatch($request, $company);
    }

    // --- PKL Plotting & Periods ---
    public function plotting(Request $request)
    {
        return app(PlottingController::class)->plotting($request);
    }

    public function storePlotting(StorePlottingRequest $request)
    {
        return app(PlottingController::class)->storePlotting($request);
    }

    public function reassignPlotting(Request $request, StudentPkl $studentPkl)
    {
        return app(PlottingController::class)->reassignPlotting($request, $studentPkl);
    }

    public function destroyPlotting(StudentPkl $studentPkl)
    {
        return app(PlottingController::class)->destroyPlotting($studentPkl);
    }

    public function sendParentLink(Request $request, StudentPkl $studentPkl, WhatsAppGatewayService $waGateway)
    {
        return app(PlottingController::class)->sendParentLink($request, $studentPkl, $waGateway);
    }

    public function storePeriod(Request $request)
    {
        return app(PlottingController::class)->storePeriod($request);
    }

    public function updatePeriod(Request $request, PklPeriod $period)
    {
        return app(PlottingController::class)->updatePeriod($request, $period);
    }

    public function togglePeriodActive(PklPeriod $period)
    {
        return app(PlottingController::class)->togglePeriodActive($period);
    }

    // --- Holidays ---
    public function holidays(Request $request, HolidayService $holidayService)
    {
        return app(HolidayController::class)->holidays($request, $holidayService);
    }

    public function storeHoliday(StoreHolidayRequest $request, HolidayService $holidayService)
    {
        return app(HolidayController::class)->storeHoliday($request, $holidayService);
    }

    public function destroyHoliday(SchoolHoliday $holiday, HolidayService $holidayService)
    {
        return app(HolidayController::class)->destroyHoliday($holiday, $holidayService);
    }

    public function syncNationalHolidays(Request $request, HolidayService $holidayService)
    {
        return app(HolidayController::class)->syncNationalHolidays($request, $holidayService);
    }

    // --- Incidents ---
    public function incidents(Request $request, IncidentReportService $incidentService)
    {
        return app(IncidentController::class)->incidents($request, $incidentService);
    }

    public function followupIncident(Request $request, IncidentReport $report, IncidentReportService $incidentService)
    {
        return app(IncidentController::class)->followupIncident($request, $report, $incidentService);
    }

    // --- Certificates ---
    public function certificates(Request $request, CertificateService $certificateService)
    {
        return app(CertificateController::class)->certificates($request, $certificateService);
    }

    public function generateCertificate(Request $request, CertificateService $certificateService)
    {
        return app(CertificateController::class)->generateCertificate($request, $certificateService);
    }

    public function generateBatchCertificates(Request $request, CertificateService $certificateService)
    {
        return app(CertificateController::class)->generateBatchCertificates($request, $certificateService);
    }

    public function downloadCertificate(Certificate $certificate, CertificateService $certificateService)
    {
        return app(CertificateController::class)->downloadCertificate($certificate, $certificateService);
    }

    // --- Billing ---
    public function billing()
    {
        return app(BillingController::class)->billing();
    }

    public function downloadInvoicePdf(Invoice $invoice)
    {
        return app(BillingController::class)->downloadInvoicePdf($invoice);
    }
}
