<?php

use App\Http\Controllers\AdminSekolahController;
use App\Http\Controllers\AdminSekolah\AcademicController;
use App\Http\Controllers\AdminSekolah\BillingController as AdminSekolahBillingController;
use App\Http\Controllers\AdminSekolah\CertificateController as AdminSekolahCertificateController;
use App\Http\Controllers\AdminSekolah\CompanyController as AdminSekolahCompanyController;
use App\Http\Controllers\AdminSekolah\DashboardController as AdminSekolahDashboardController;
use App\Http\Controllers\AdminSekolah\HolidayController as AdminSekolahHolidayController;
use App\Http\Controllers\AdminSekolah\IncidentController as AdminSekolahIncidentController;
use App\Http\Controllers\AdminSekolah\PlottingController as AdminSekolahPlottingController;
use App\Http\Controllers\AdminSekolah\SettingController as AdminSekolahSettingController;
use App\Http\Controllers\AdminSekolah\StudentController as AdminSekolahStudentController;
use App\Http\Controllers\AdminSekolah\TeacherController as AdminSekolahTeacherController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\KajurController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\KepsekController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ParentLinkController;
use App\Http\Controllers\ParentMonitoringController;
use App\Http\Controllers\PklMapController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\SecureFileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\BackupController;
use App\Http\Controllers\SuperAdmin\BroadcastController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\InvoiceController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\SchoolRequestController;
use App\Http\Controllers\SuperAdmin\SettingController as SuperAdminSettingController;
use App\Http\Controllers\SuperAdmin\SystemHealthController;
use App\Http\Controllers\SuperAdmin\TenantController;
use App\Http\Controllers\SuperAdmin\WhatsAppGatewayController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\WaliKelasController;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::get('/', [LandingController::class, 'index'])->name('landing');

// PWA Web Manifests (Siswa & Guru — di-generate saat pengaturan berubah)
$servePwaManifest = function (string $filename) {
    $path = public_path($filename);
    if (!file_exists($path)) {
        \App\Models\PlatformSetting::syncPwaManifest();
    }
    $manifest = json_decode(@file_get_contents($path), true) ?: [];
    return response()->json($manifest, 200, [
        'Content-Type' => 'application/manifest+json; charset=utf-8',
        'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=3600',
    ]);
};

Route::get('/manifest.webmanifest', fn () => $servePwaManifest('manifest.webmanifest'))->name('pwa.manifest');
Route::get('/manifest-guru.webmanifest', fn () => $servePwaManifest('manifest-guru.webmanifest'))->name('pwa.manifest.teacher');

Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()])
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
})->name('csrf.token');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:180,1')->name('login.post');
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'requestReset'])->middleware('throttle:5,15')->name('password.email');

// Guest Public Verification for E-Sertifikat PKL (QR Code Destination)
Route::get('/verify/cert/{uuid}', [CertificateVerificationController::class, 'verify'])->middleware('throttle:60,1')->name('public.cert.verify');
Route::get('/verify/cert/{uuid}/download', [CertificateVerificationController::class, 'download'])->middleware('throttle:30,1')->name('public.cert.download');

// Guest Public Parent Monitoring Portal (No Login Required)
Route::get('/pantau/{token}', [ParentMonitoringController::class, 'show'])->middleware('throttle:60,1')->name('parent.monitor');
Route::get('/pantau/{token}/foto/{photo}', [SecureFileController::class, 'parentJournalPhoto'])->middleware('throttle:120,1')->whereNumber('photo')->name('parent.monitor.photo');

// Berkas pribadi siswa (selfie, foto jurnal, lampiran) — disajikan dari disk privat dengan cek hak akses
Route::middleware(['auth', 'IdentifyTenant'])->get('/berkas/{type}/{id}', [SecureFileController::class, 'show'])
    ->whereIn('type', array_keys(\App\Services\SecureFileService::TYPES))
    ->whereNumber('id')
    ->name('secure.file');

// Shared Authenticated Notification Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

    // Web Push Subscriptions (PWA Android & iOS 16.4+)
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
    Route::post('/push-subscriptions/test', [PushSubscriptionController::class, 'sendTest'])->middleware('throttle:6,1')->name('push-subscriptions.test');

    // Impersonation Routes
    Route::post('/impersonate/leave', [ImpersonationController::class, 'stop'])->name('impersonate.leave');
    Route::post('/impersonate/{user}', [ImpersonationController::class, 'start'])->name('impersonate.start');

    // Self-Service Password Change & Contact/Preferences for All User Levels
    Route::post('/profile/change-password', [AuthController::class, 'changePassword'])->name('profile.change-password');
    Route::post('/profile/contact-preferences', [AuthController::class, 'updateContactAndPreferences'])->name('profile.contact-preferences');
});

// 1. Super Admin Platform Routes (Modular SaaS Architecture)
Route::middleware(['auth', 'role:super-admin'])->prefix('super-admin')->group(function () {
    // SaaS Dashboard & Metrik
    Route::get('/dashboard', [SuperAdminDashboardController::class, 'dashboard'])->name('superadmin.dashboard');

    // Tenant & Manajemen Sekolah Multi-Tenancy
    Route::get('/schools', [TenantController::class, 'schools'])->name('superadmin.schools');
    Route::get('/schools/export', [TenantController::class, 'exportSchools'])->name('superadmin.schools.export');
    Route::get('/schools/{school}', [TenantController::class, 'show'])->whereNumber('school')->name('superadmin.schools.show');
    Route::post('/schools', [TenantController::class, 'storeSchool'])->name('superadmin.schools.store');
    Route::put('/schools/{school}', [TenantController::class, 'updateSchool'])->name('superadmin.schools.update');
    Route::put('/schools/{school}/features', [TenantController::class, 'updateSchoolFeatures'])->name('superadmin.schools.update-features');
    Route::delete('/schools/{school}', [TenantController::class, 'destroySchool'])->name('superadmin.schools.destroy');
    Route::post('/schools/{school}/toggle', [TenantController::class, 'toggleSchool'])->name('superadmin.schools.toggle');
    Route::post('/schools/{school}/extend', [TenantController::class, 'extendSubscription'])->name('superadmin.schools.extend');
    Route::post('/schools/{school}/impersonate', [TenantController::class, 'impersonateSchool'])->name('superadmin.schools.impersonate');
    Route::post('/schools/{school}/reset-admin-password', [TenantController::class, 'resetAdminPassword'])->name('superadmin.schools.reset-admin-password');

    // Storage Quota & Offboarding Export
    Route::post('/schools/{school}/storage/recalculate', [TenantController::class, 'recalculateSchoolStorage'])->name('superadmin.schools.recalculate-storage');
    Route::post('/schools/{school}/storage/quota', [TenantController::class, 'updateSchoolStorageQuota'])->name('superadmin.schools.update-storage-quota');
    Route::get('/schools/{school}/offboard-export', [TenantController::class, 'exportSchoolArchive'])->name('superadmin.schools.offboard-export');

    // SaaS Invoices & Billing
    Route::get('/invoices', [InvoiceController::class, 'invoices'])->name('superadmin.invoices');
    Route::post('/invoices', [InvoiceController::class, 'storeInvoice'])->name('superadmin.invoices.store');
    Route::post('/invoices/{invoice}/verify', [InvoiceController::class, 'markInvoicePaid'])->name('superadmin.invoices.verify');
    Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancelInvoice'])->name('superadmin.invoices.cancel');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroyInvoice'])->name('superadmin.invoices.destroy');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadInvoicePdf'])->name('superadmin.invoices.pdf');

    // SaaS Plans & Licensing
    Route::get('/plans', [PlanController::class, 'plans'])->name('superadmin.plans');
    Route::post('/plans', [PlanController::class, 'storePlan'])->name('superadmin.plans.store');
    Route::put('/plans/{plan}', [PlanController::class, 'updatePlan'])->name('superadmin.plans.update');
    Route::delete('/plans/{plan}', [PlanController::class, 'destroyPlan'])->name('superadmin.plans.destroy');

    // Platform Settings & System Logo
    Route::get('/settings', [SuperAdminSettingController::class, 'settings'])->name('superadmin.settings');
    Route::post('/settings', [SuperAdminSettingController::class, 'updateSettings'])->name('superadmin.settings.update');

    // Audit Trail & Activity Logs
    Route::get('/audit-logs', [AuditLogController::class, 'auditLogs'])->name('superadmin.audit-logs');

    // DevOps, Queue & System Health
    Route::get('/system', [SystemHealthController::class, 'systemHealth'])->name('superadmin.system-health');
    Route::post('/system/retry-job/{id}', [SystemHealthController::class, 'retryFailedJob'])->name('superadmin.system.retry-job');
    Route::delete('/system/delete-job/{id}', [SystemHealthController::class, 'deleteFailedJob'])->name('superadmin.system.delete-job');
    Route::post('/system/flush-jobs', [SystemHealthController::class, 'flushFailedJobs'])->name('superadmin.system.flush-jobs');
    Route::post('/system/clear-cache', [SystemHealthController::class, 'clearSystemCache'])->name('superadmin.system.clear-cache');

    // WhatsApp Gateway Mandiri (Baileys)
    Route::get('/whatsapp', [WhatsAppGatewayController::class, 'index'])->name('superadmin.whatsapp');
    Route::get('/whatsapp/status', [WhatsAppGatewayController::class, 'ajaxStatus'])->name('superadmin.whatsapp.status');
    Route::post('/whatsapp/test', [WhatsAppGatewayController::class, 'testSend'])->name('superadmin.whatsapp.test');
    Route::post('/whatsapp/logout', [WhatsAppGatewayController::class, 'logout'])->name('superadmin.whatsapp.logout');

    // Database Backups & Pemulihan (Restore)
    Route::get('/backups', [BackupController::class, 'backups'])->name('superadmin.backups');
    Route::post('/backups/create', [BackupController::class, 'createBackup'])->name('superadmin.backups.create');
    Route::get('/backups/download/{filename}', [BackupController::class, 'downloadBackup'])->name('superadmin.backups.download');
    Route::delete('/backups/delete/{filename}', [BackupController::class, 'deleteBackup'])->name('superadmin.backups.delete');
    Route::post('/backups/restore/{filename}', [BackupController::class, 'restoreBackup'])->name('superadmin.backups.restore');

    // Platform Announcements / Broadcasts
    Route::get('/broadcasts', [BroadcastController::class, 'broadcasts'])->name('superadmin.broadcasts');
    Route::post('/broadcasts', [BroadcastController::class, 'storeBroadcast'])->name('superadmin.broadcasts.store');
    Route::put('/broadcasts/{broadcast}', [BroadcastController::class, 'updateBroadcast'])->name('superadmin.broadcasts.update');
    Route::post('/broadcasts/{broadcast}/toggle', [BroadcastController::class, 'toggleBroadcast'])->name('superadmin.broadcasts.toggle');
    Route::delete('/broadcasts/{broadcast}', [BroadcastController::class, 'destroyBroadcast'])->name('superadmin.broadcasts.destroy');

    // School Requests (Demo Pipeline & Onboarding)
    Route::get('/school-requests', [SchoolRequestController::class, 'schoolRequests'])->name('superadmin.school-requests');
    Route::post('/school-requests/{schoolRequest}/approve', [SchoolRequestController::class, 'approveSchoolRequest'])->name('superadmin.school-requests.approve');
    Route::post('/school-requests/{schoolRequest}/reject', [SchoolRequestController::class, 'rejectSchoolRequest'])->name('superadmin.school-requests.reject');
});

// Public Demo Request Route
Route::post('/request-demo', [SchoolRequestController::class, 'storePublicSchoolRequest'])->middleware('throttle:5,10')->name('public.request-demo');

// 2. Admin Sekolah Routes
Route::middleware(['auth', 'IdentifyTenant', 'role:admin-sekolah'])->prefix('admin-sekolah')->group(function () {
    Route::get('/dashboard', [AdminSekolahDashboardController::class, 'dashboard'])->name('adminsekolah.dashboard');

    // Settings
    Route::get('/settings', [AdminSekolahSettingController::class, 'settings'])->name('adminsekolah.settings');
    Route::post('/settings', [AdminSekolahSettingController::class, 'updateSettings'])->name('adminsekolah.settings.update');
    Route::post('/test-mode/toggle', [AdminSekolahSettingController::class, 'toggleTestMode'])->name('adminsekolah.test-mode.toggle');
    Route::get('/test-mode/stats', [AdminSekolahSettingController::class, 'getTestModeStats'])->name('adminsekolah.test-mode.stats');
    Route::post('/test-mode/clean', [AdminSekolahSettingController::class, 'cleanTestData'])->name('adminsekolah.test-mode.clean');

    // Majors & Classes
    Route::get('/majors', [AcademicController::class, 'majors'])->name('adminsekolah.majors');
    Route::post('/majors', [AcademicController::class, 'storeMajor'])->name('adminsekolah.majors.store');
    Route::put('/majors/{major}', [AcademicController::class, 'updateMajor'])->name('adminsekolah.majors.update');
    Route::delete('/majors/{major}', [AcademicController::class, 'destroyMajor'])->name('adminsekolah.majors.destroy');
    Route::post('/classes', [AcademicController::class, 'storeClass'])->name('adminsekolah.classes.store');
    Route::put('/classes/{schoolClass}', [AcademicController::class, 'updateClass'])->name('adminsekolah.classes.update');
    Route::delete('/classes/{schoolClass}', [AcademicController::class, 'destroyClass'])->name('adminsekolah.classes.destroy');
    Route::get('/classes/promotion-preview', [AcademicController::class, 'previewPromotion'])->name('adminsekolah.classes.promotion-preview');
    Route::post('/classes/promote', [AcademicController::class, 'promoteClasses'])->name('adminsekolah.classes.promote');
    Route::post('/majors/{major}/competencies', [AcademicController::class, 'storeMajorCompetency'])->name('adminsekolah.majors.competencies.store');
    Route::put('/competencies/{learningObjective}', [AcademicController::class, 'updateMajorCompetency'])->name('adminsekolah.competencies.update');
    Route::delete('/competencies/{learningObjective}', [AcademicController::class, 'destroyMajorCompetency'])->name('adminsekolah.competencies.destroy');

    // Students & Excel Import & Export
    Route::get('/students', [AdminSekolahStudentController::class, 'students'])->name('adminsekolah.students');
    Route::get('/students/export', [AdminSekolahStudentController::class, 'exportStudents'])->name('adminsekolah.students.export');
    Route::post('/students', [AdminSekolahStudentController::class, 'storeStudent'])->name('adminsekolah.students.store');
    Route::put('/students/{student}', [AdminSekolahStudentController::class, 'updateStudent'])->name('adminsekolah.students.update');
    Route::post('/students/{student}/reset-password', [AdminSekolahStudentController::class, 'resetStudentPassword'])->name('adminsekolah.students.reset-password');
    Route::delete('/students/{student}', [AdminSekolahStudentController::class, 'destroyStudent'])->name('adminsekolah.students.destroy');
    Route::post('/students/import', [AdminSekolahStudentController::class, 'importStudents'])->name('adminsekolah.students.import');

    // Teachers & Excel Export
    Route::get('/teachers', [AdminSekolahTeacherController::class, 'teachers'])->name('adminsekolah.teachers');
    Route::get('/teachers/export', [AdminSekolahTeacherController::class, 'exportTeachers'])->name('adminsekolah.teachers.export');
    Route::get('/teachers/export-leadership', [AdminSekolahTeacherController::class, 'exportLeadership'])->name('adminsekolah.teachers.export-leadership');
    Route::post('/teachers', [AdminSekolahTeacherController::class, 'storeTeacher'])->name('adminsekolah.teachers.store');
    Route::put('/teachers/{teacher}', [AdminSekolahTeacherController::class, 'updateTeacher'])->name('adminsekolah.teachers.update');
    Route::post('/teachers/{teacher}/reset-password', [AdminSekolahTeacherController::class, 'resetTeacherPassword'])->name('adminsekolah.teachers.reset-password');
    Route::delete('/teachers/{teacher}', [AdminSekolahTeacherController::class, 'destroyTeacher'])->name('adminsekolah.teachers.destroy');

    // DU/DI Companies & Supervisors
    Route::get('/companies', [AdminSekolahCompanyController::class, 'companies'])->name('adminsekolah.companies');
    Route::post('/companies', [AdminSekolahCompanyController::class, 'storeCompany'])->name('adminsekolah.companies.store');
    Route::put('/companies/{company}', [AdminSekolahCompanyController::class, 'updateCompany'])->name('adminsekolah.companies.update');
    Route::delete('/companies/{company}', [AdminSekolahCompanyController::class, 'destroyCompany'])->name('adminsekolah.companies.destroy');
    Route::post('/companies/{company}/supervisors', [AdminSekolahCompanyController::class, 'storeCompanySupervisor'])->name('adminsekolah.companies.supervisors.store');
    Route::put('/companies/{company}/supervisors/{supervisor}', [AdminSekolahCompanyController::class, 'updateCompanySupervisor'])->name('adminsekolah.companies.supervisors.update');
    Route::delete('/companies/{company}/supervisors/{supervisor}', [AdminSekolahCompanyController::class, 'destroyCompanySupervisor'])->name('adminsekolah.companies.supervisors.destroy');
    Route::post('/companies/{company}/reassign-batch', [AdminSekolahCompanyController::class, 'reassignCompanyBatch'])->name('adminsekolah.companies.reassign-batch');

    // Plotting PKL
    Route::get('/plotting', [AdminSekolahPlottingController::class, 'plotting'])->name('adminsekolah.plotting');
    Route::post('/plotting', [AdminSekolahPlottingController::class, 'storePlotting'])->name('adminsekolah.plotting.store');
    Route::post('/plotting/{studentPkl}/send-parent-link', [AdminSekolahPlottingController::class, 'sendParentLink'])->name('adminsekolah.plotting.send-parent-link');
    Route::put('/plotting/{studentPkl}/reassign', [AdminSekolahPlottingController::class, 'reassignPlotting'])->name('adminsekolah.plotting.reassign');
    Route::delete('/plotting/{studentPkl}', [AdminSekolahPlottingController::class, 'destroyPlotting'])->name('adminsekolah.plotting.destroy');

    // Periode Pelaksanaan PKL
    Route::post('/periods', [AdminSekolahPlottingController::class, 'storePeriod'])->name('adminsekolah.periods.store');
    Route::put('/periods/{period}', [AdminSekolahPlottingController::class, 'updatePeriod'])->name('adminsekolah.periods.update');
    Route::post('/periods/{period}/toggle-active', [AdminSekolahPlottingController::class, 'togglePeriodActive'])->name('adminsekolah.periods.toggle-active');

    // Announcements / Pengumuman
    Route::get('/announcements', [AnnouncementController::class, 'adminIndex'])->name('adminsekolah.announcements');
    Route::post('/announcements', [AnnouncementController::class, 'adminStore'])->name('adminsekolah.announcements.store');
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'adminUpdate'])->name('adminsekolah.announcements.update');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'adminDestroy'])->name('adminsekolah.announcements.destroy');
    Route::post('/announcements/{announcement}/toggle-pin', [AnnouncementController::class, 'adminTogglePin'])->name('adminsekolah.announcements.toggle-pin');

    // Kalender Hari Libur
    Route::get('/holidays', [AdminSekolahHolidayController::class, 'holidays'])->name('adminsekolah.holidays');
    Route::post('/holidays', [AdminSekolahHolidayController::class, 'storeHoliday'])->name('adminsekolah.holidays.store');
    Route::delete('/holidays/{holiday}', [AdminSekolahHolidayController::class, 'destroyHoliday'])->name('adminsekolah.holidays.destroy');
    Route::post('/holidays/sync-national', [AdminSekolahHolidayController::class, 'syncNationalHolidays'])->name('adminsekolah.holidays.sync-national');

    // Laporan Kendala & SOS Siswa
    Route::get('/incidents', [AdminSekolahIncidentController::class, 'incidents'])->name('adminsekolah.incidents');
    Route::post('/incidents/{report}/followup', [AdminSekolahIncidentController::class, 'followupIncident'])->name('adminsekolah.incidents.followup');

    // E-Sertifikat & Transkrip PKL
    Route::get('/certificates', [AdminSekolahCertificateController::class, 'certificates'])->name('adminsekolah.certificates');
    Route::post('/certificates/generate', [AdminSekolahCertificateController::class, 'generateCertificate'])->name('adminsekolah.certificates.generate');
    Route::post('/certificates/batch', [AdminSekolahCertificateController::class, 'generateBatchCertificates'])->name('adminsekolah.certificates.batch');
    Route::get('/certificates/{certificate}/download', [AdminSekolahCertificateController::class, 'downloadCertificate'])->name('adminsekolah.certificates.download');

    // Link Monitoring Portal Orang Tua (Batch & Export per Jurusan & Rombel)
    Route::get('/parent-links', [ParentLinkController::class, 'adminIndex'])->name('adminsekolah.parent-links');
    Route::post('/parent-links/batch-generate', [ParentLinkController::class, 'adminBatchGenerate'])->name('adminsekolah.parent-links.batch-generate');
    Route::post('/parent-links/batch-toggle', [ParentLinkController::class, 'adminBatchToggle'])->name('adminsekolah.parent-links.batch-toggle');
    Route::get('/parent-links/export', [ParentLinkController::class, 'adminExport'])->name('adminsekolah.parent-links.export');
    Route::post('/parent-links/{student}/regenerate', [ParentLinkController::class, 'adminRegenerateSingle'])->name('adminsekolah.parent-links.regenerate');
    Route::post('/parent-links/{student}/toggle', [ParentLinkController::class, 'adminToggleSingle'])->name('adminsekolah.parent-links.toggle');

    // Peta Interaktif Sebaran Siswa PKL & DU/DI
    Route::get('/peta-sebaran', [PklMapController::class, 'adminMap'])->name('adminsekolah.map');

    // Portal Billing & Langganan SaaS Tenant
    Route::get('/billing', [AdminSekolahBillingController::class, 'billing'])->name('adminsekolah.billing');
    Route::get('/invoices/{invoice}/pdf', [AdminSekolahBillingController::class, 'downloadInvoicePdf'])->name('adminsekolah.invoices.pdf');
});

// Backward compatibility alias for /adminsekolah (without hyphen)
Route::middleware(['auth', 'IdentifyTenant', 'role:admin-sekolah'])->prefix('adminsekolah')->group(function () {
    Route::post('/periods', [AdminSekolahController::class, 'storePeriod']);
    Route::put('/periods/{period}', [AdminSekolahController::class, 'updatePeriod']);
    Route::post('/periods/{period}/toggle-active', [AdminSekolahController::class, 'togglePeriodActive']);
});

// 3. Siswa (Mobile-First) Routes
Route::middleware(['auth', 'IdentifyTenant', 'role:siswa'])->prefix('siswa')->group(function () {
    Route::get('/beranda', [StudentController::class, 'home'])->name('student.home');
    Route::get('/absensi', [StudentController::class, 'attendance'])->name('student.attendance');
    Route::post('/checkin', [StudentController::class, 'checkin'])->middleware('throttle:15,1')->name('student.checkin');
    Route::post('/absensi/checkin', [StudentController::class, 'checkin'])->middleware('throttle:15,1')->name('student.attendance.checkin');
    Route::post('/checkout', [StudentController::class, 'checkout'])->middleware('throttle:15,1')->name('student.checkout');
    Route::post('/permohonan', [StudentController::class, 'storeRequest'])->name('student.request.store');
    Route::post('/lapor-kendala', [StudentController::class, 'storeIncident'])->name('student.incident.store');

    Route::get('/jurnal', [StudentController::class, 'journals'])->name('student.journals');
    Route::post('/jurnal', [StudentController::class, 'storeJournal'])->name('student.journal.store');
    Route::post('/jurnal/{journal}/submit', [StudentController::class, 'submitJournal'])->name('student.journal.submit');
    Route::delete('/jurnal/{journal}', [StudentController::class, 'destroyJournal'])->name('student.journal.destroy');

    Route::get('/profil', [StudentController::class, 'profile'])->name('student.profile');
    Route::get('/kendala', [StudentController::class, 'incidents'])->name('student.incidents');
    Route::post('/kendala/{report}/followup', [StudentController::class, 'followupIncident'])->name('student.incident.followup');
    Route::post('/parent-token/regenerate', [ParentMonitoringController::class, 'regenerate'])->middleware('throttle:3,1440')->name('student.parent-token.regenerate');
    Route::get('/sertifikat/download', [StudentController::class, 'downloadCertificate'])->name('student.certificate.download');

    // Pengumuman Siswa
    Route::get('/pengumuman', [AnnouncementController::class, 'studentIndex'])->name('student.announcements');
    Route::get('/pengumuman/latest/check', [AnnouncementController::class, 'latestForStudent'])->name('student.announcements.latest');
    Route::get('/pengumuman/{announcement}', [AnnouncementController::class, 'show'])->name('student.announcements.show');
    Route::post('/pengumuman/{announcement}/read', [AnnouncementController::class, 'markAsRead'])->name('student.announcements.read');
});

// 4. Guru Pembimbing Routes
Route::middleware(['auth', 'IdentifyTenant', 'role:guru-pembimbing'])->prefix('guru')->group(function () {
    Route::get('/dashboard', [TeacherController::class, 'dashboard'])->name('guru.dashboard');
    Route::get('/monitoring', [TeacherController::class, 'monitoring'])->name('guru.monitoring');
    Route::post('/monitoring/override-attendance', [TeacherController::class, 'overrideAttendance'])->name('guru.attendance.override');
    Route::post('/students/{student}/reset-password', [TeacherController::class, 'resetStudentPassword'])->middleware('throttle:5,1')->name('guru.students.reset-password');
    Route::get('/requests', [TeacherController::class, 'requests'])->name('guru.requests');
    Route::post('/requests/bulk', [TeacherController::class, 'bulkProcessRequests'])->name('guru.requests.bulk');
    Route::post('/requests/{attRequest}/process', [TeacherController::class, 'processRequest'])->name('guru.requests.process');

    Route::get('/journals', [TeacherController::class, 'journals'])->name('guru.journals');
    Route::post('/journals/bulk', [TeacherController::class, 'bulkProcessJournals'])->name('guru.journals.bulk');
    Route::post('/journals/{journal}/process', [TeacherController::class, 'processJournal'])->name('guru.journals.process');

    // Laporan Kendala & SOS Siswa Bimbingan
    Route::get('/incidents', [TeacherController::class, 'incidents'])->name('guru.incidents');
    Route::post('/incidents/{report}/followup', [TeacherController::class, 'followupIncident'])->name('guru.incidents.followup');

    // Supervisi Lapangan & Berita Acara Kunjungan Guru
    Route::get('/supervisions', [TeacherController::class, 'supervisions'])->name('guru.supervisions');
    Route::post('/supervisions', [TeacherController::class, 'storeSupervision'])->name('guru.supervisions.store');
    Route::get('/supervisions/{supervisionVisit}/pdf', [TeacherController::class, 'downloadBeritaAcaraPdf'])->name('guru.supervisions.pdf');

    Route::get('/assessments', [TeacherController::class, 'assessments'])->name('guru.assessments');
    Route::post('/assessments', [TeacherController::class, 'storeAssessment'])->name('guru.assessments.store');

    Route::get('/reports', [TeacherController::class, 'reports'])->name('guru.reports');
    Route::get('/reports/{spkl}/download', [TeacherController::class, 'downloadPdf'])->name('guru.reports.download');

    // Announcements / Pengumuman Guru
    Route::get('/announcements', [AnnouncementController::class, 'guruIndex'])->name('guru.announcements');
    Route::post('/announcements', [AnnouncementController::class, 'guruStore'])->name('guru.announcements.store');
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'guruUpdate'])->name('guru.announcements.update');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'guruDestroy'])->name('guru.announcements.destroy');

    // Link Monitoring Portal Orang Tua Siswa Bimbingan (Batch & Export per Jurusan & Rombel)
    Route::get('/parent-links', [ParentLinkController::class, 'guruIndex'])->name('guru.parent-links');
    Route::post('/parent-links/batch-generate', [ParentLinkController::class, 'guruBatchGenerate'])->name('guru.parent-links.batch-generate');
    Route::post('/parent-links/batch-toggle', [ParentLinkController::class, 'guruBatchToggle'])->name('guru.parent-links.batch-toggle');
    Route::get('/parent-links/export', [ParentLinkController::class, 'guruExport'])->name('guru.parent-links.export');
    Route::post('/parent-links/{student}/regenerate', [ParentLinkController::class, 'guruRegenerateSingle'])->name('guru.parent-links.regenerate');
    Route::post('/parent-links/{student}/toggle', [ParentLinkController::class, 'guruToggleSingle'])->name('guru.parent-links.toggle');

    // Peta Interaktif Sebaran Siswa PKL Bimbingan & DU/DI
    Route::get('/peta-sebaran', [PklMapController::class, 'guruMap'])->name('guru.map');

    // Update Profil Mandiri Guru Pembimbing (Nama, NIP, Nomor HP/WA)
    Route::post('/profile', [TeacherController::class, 'updateProfile'])->name('guru.profile.update');
});

// 4.1 Kepala Jurusan / Kepala Program Keahlian Routes
Route::middleware(['auth', 'IdentifyTenant', 'is-kajur'])->prefix('kajur')->group(function () {
    Route::get('/dashboard', [KajurController::class, 'dashboard'])->name('kajur.dashboard');

    // Mitra Perusahaan (DU/DI)
    Route::get('/companies', [KajurController::class, 'companies'])->name('kajur.companies');
    Route::post('/companies', [KajurController::class, 'storeCompany'])->name('kajur.companies.store');
    Route::put('/companies/{company}', [KajurController::class, 'updateCompany'])->name('kajur.companies.update');
    Route::delete('/companies/{company}', [KajurController::class, 'destroyCompany'])->name('kajur.companies.destroy');

    // Siswa & Plotting PKL Jurusan
    Route::get('/students', [KajurController::class, 'students'])->name('kajur.students');
    Route::post('/students/{student}/assign', [KajurController::class, 'assignPlotting'])->name('kajur.students.assign');
    Route::post('/students/{student}/reset-password', [KajurController::class, 'resetStudentPassword'])->middleware('throttle:5,1')->name('kajur.students.reset-password');

    // Monitoring Presensi & Jurnal
    Route::get('/attendance', [KajurController::class, 'attendance'])->name('kajur.attendance');
    Route::get('/journals', [KajurController::class, 'journals'])->name('kajur.journals');

    // Supervisi Guru & Nilai
    Route::get('/supervisions', [KajurController::class, 'supervisions'])->name('kajur.supervisions');
    Route::get('/grades', [KajurController::class, 'grades'])->name('kajur.grades');

    // Capaian Pembelajaran (CP) / Elemen Kompetensi
    Route::get('/competencies', [KajurController::class, 'competencies'])->name('kajur.competencies');
    Route::post('/competencies', [KajurController::class, 'storeCompetency'])->name('kajur.competencies.store');
    Route::put('/competencies/{learningObjective}', [KajurController::class, 'updateCompetency'])->name('kajur.competencies.update');
    Route::delete('/competencies/{learningObjective}', [KajurController::class, 'destroyCompetency'])->name('kajur.competencies.destroy');
});

// 4.2 Wali Kelas (Homeroom Teacher) Routes
Route::middleware(['auth', 'IdentifyTenant', 'is-walikelas'])->prefix('wali-kelas')->group(function () {
    Route::get('/dashboard', [WaliKelasController::class, 'dashboard'])->name('walikelas.dashboard');
    Route::get('/students', [WaliKelasController::class, 'students'])->name('walikelas.students');
    Route::post('/students/{student}/reset-password', [WaliKelasController::class, 'resetStudentPassword'])->middleware('throttle:5,1')->name('walikelas.students.reset-password');
    Route::get('/attendance', [WaliKelasController::class, 'attendance'])->name('walikelas.attendance');
    Route::get('/journals', [WaliKelasController::class, 'journals'])->name('walikelas.journals');
    Route::get('/incidents', [WaliKelasController::class, 'incidents'])->name('walikelas.incidents');
    Route::get('/grades', [WaliKelasController::class, 'grades'])->name('walikelas.grades');

    // Link Monitoring Portal Orang Tua khusus rombel ampuannya
    Route::get('/parent-links', [ParentLinkController::class, 'walikelasIndex'])->name('walikelas.parent-links');
    Route::post('/parent-links/batch-generate', [ParentLinkController::class, 'walikelasBatchGenerate'])->name('walikelas.parent-links.batch-generate');
    Route::post('/parent-links/batch-toggle', [ParentLinkController::class, 'walikelasBatchToggle'])->name('walikelas.parent-links.batch-toggle');
    Route::get('/parent-links/export', [ParentLinkController::class, 'walikelasExport'])->name('walikelas.parent-links.export');
    Route::post('/parent-links/{student}/regenerate', [ParentLinkController::class, 'walikelasRegenerateSingle'])->name('walikelas.parent-links.regenerate');
    Route::post('/parent-links/{student}/toggle', [ParentLinkController::class, 'walikelasToggleSingle'])->name('walikelas.parent-links.toggle');
});

// 5. Pembimbing DU/DI Routes
Route::middleware(['auth', 'IdentifyTenant', 'role:pembimbing-dudi'])->prefix('dudi')->group(function () {
    Route::get('/dashboard', [SupervisorController::class, 'dashboard'])->name('dudi.dashboard');
    Route::get('/attendances', [SupervisorController::class, 'attendances'])->name('dudi.attendances');
    Route::get('/requests', [SupervisorController::class, 'requests'])->name('dudi.requests');
    Route::post('/requests/bulk', [SupervisorController::class, 'bulkProcessRequests'])->name('dudi.requests.bulk');
    Route::post('/requests/{attRequest}/process', [SupervisorController::class, 'processRequest'])->name('dudi.requests.process');

    Route::get('/journals', [SupervisorController::class, 'journals'])->name('dudi.journals');
    Route::post('/journals/bulk', [SupervisorController::class, 'bulkProcessJournals'])->name('dudi.journals.bulk');
    Route::post('/journals/{journal}/process', [SupervisorController::class, 'processJournal'])->name('dudi.journals.process');

    Route::get('/assessments', [SupervisorController::class, 'assessments'])->name('dudi.assessments');
    Route::post('/assessments', [SupervisorController::class, 'storeAssessment'])->name('dudi.assessments.store');
});

// 6. Kepala Sekolah Routes
Route::middleware(['auth', 'IdentifyTenant', 'role:kepala-sekolah'])->prefix('kepala-sekolah')->group(function () {
    Route::get('/dashboard', [KepsekController::class, 'dashboard'])->name('kepsek.dashboard');
    Route::get('/rekap', [KepsekController::class, 'rekap'])->name('kepsek.rekap');
    Route::get('/rekap/export', [KepsekController::class, 'exportRekap'])->name('kepsek.rekap.export');
    Route::get('/peta-sebaran', [PklMapController::class, 'kepsekMap'])->name('kepsek.map');
});
