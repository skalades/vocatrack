<?php

use App\Http\Controllers\AdminSekolahController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\KepsekController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ParentLinkController;
use App\Http\Controllers\ParentMonitoringController;
use App\Http\Controllers\PklMapController;
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
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::get('/', [LandingController::class, 'index'])->name('landing');

// PWA Web Manifest (Tersinkronisasi otomatis dengan logo & identitas sistem)
Route::get('/manifest.webmanifest', function () {
    \App\Models\PlatformSetting::syncPwaManifest();
    $manifest = json_decode(@file_get_contents(public_path('manifest.webmanifest')), true) ?: [];
    return response()->json($manifest, 200, [
        'Content-Type' => 'application/manifest+json; charset=utf-8',
        'Cache-Control' => 'no-cache, private',
    ]);
})->name('pwa.manifest');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
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
    Route::get('/dashboard', [AdminSekolahController::class, 'dashboard'])->name('adminsekolah.dashboard');

    // Settings
    Route::get('/settings', [AdminSekolahController::class, 'settings'])->name('adminsekolah.settings');
    Route::post('/settings', [AdminSekolahController::class, 'updateSettings'])->name('adminsekolah.settings.update');

    // Majors & Classes
    Route::get('/majors', [AdminSekolahController::class, 'majors'])->name('adminsekolah.majors');
    Route::post('/majors', [AdminSekolahController::class, 'storeMajor'])->name('adminsekolah.majors.store');
    Route::put('/majors/{major}', [AdminSekolahController::class, 'updateMajor'])->name('adminsekolah.majors.update');
    Route::delete('/majors/{major}', [AdminSekolahController::class, 'destroyMajor'])->name('adminsekolah.majors.destroy');
    Route::post('/classes', [AdminSekolahController::class, 'storeClass'])->name('adminsekolah.classes.store');
    Route::put('/classes/{schoolClass}', [AdminSekolahController::class, 'updateClass'])->name('adminsekolah.classes.update');
    Route::delete('/classes/{schoolClass}', [AdminSekolahController::class, 'destroyClass'])->name('adminsekolah.classes.destroy');

    // Students & Excel Import
    Route::get('/students', [AdminSekolahController::class, 'students'])->name('adminsekolah.students');
    Route::post('/students', [AdminSekolahController::class, 'storeStudent'])->name('adminsekolah.students.store');
    Route::put('/students/{student}', [AdminSekolahController::class, 'updateStudent'])->name('adminsekolah.students.update');
    Route::post('/students/{student}/reset-password', [AdminSekolahController::class, 'resetStudentPassword'])->name('adminsekolah.students.reset-password');
    Route::delete('/students/{student}', [AdminSekolahController::class, 'destroyStudent'])->name('adminsekolah.students.destroy');
    Route::post('/students/import', [AdminSekolahController::class, 'importStudents'])->name('adminsekolah.students.import');

    // Teachers
    Route::get('/teachers', [AdminSekolahController::class, 'teachers'])->name('adminsekolah.teachers');
    Route::post('/teachers', [AdminSekolahController::class, 'storeTeacher'])->name('adminsekolah.teachers.store');
    Route::put('/teachers/{teacher}', [AdminSekolahController::class, 'updateTeacher'])->name('adminsekolah.teachers.update');
    Route::post('/teachers/{teacher}/reset-password', [AdminSekolahController::class, 'resetTeacherPassword'])->name('adminsekolah.teachers.reset-password');
    Route::delete('/teachers/{teacher}', [AdminSekolahController::class, 'destroyTeacher'])->name('adminsekolah.teachers.destroy');

    // DU/DI Companies & Supervisors
    Route::get('/companies', [AdminSekolahController::class, 'companies'])->name('adminsekolah.companies');
    Route::post('/companies', [AdminSekolahController::class, 'storeCompany'])->name('adminsekolah.companies.store');
    Route::put('/companies/{company}', [AdminSekolahController::class, 'updateCompany'])->name('adminsekolah.companies.update');
    Route::delete('/companies/{company}', [AdminSekolahController::class, 'destroyCompany'])->name('adminsekolah.companies.destroy');
    Route::post('/companies/{company}/supervisors', [AdminSekolahController::class, 'storeCompanySupervisor'])->name('adminsekolah.companies.supervisors.store');
    Route::put('/companies/{company}/supervisors/{supervisor}', [AdminSekolahController::class, 'updateCompanySupervisor'])->name('adminsekolah.companies.supervisors.update');
    Route::delete('/companies/{company}/supervisors/{supervisor}', [AdminSekolahController::class, 'destroyCompanySupervisor'])->name('adminsekolah.companies.supervisors.destroy');
    Route::post('/companies/{company}/reassign-batch', [AdminSekolahController::class, 'reassignCompanyBatch'])->name('adminsekolah.companies.reassign-batch');

    // Plotting PKL
    Route::get('/plotting', [AdminSekolahController::class, 'plotting'])->name('adminsekolah.plotting');
    Route::post('/plotting', [AdminSekolahController::class, 'storePlotting'])->name('adminsekolah.plotting.store');
    Route::put('/plotting/{studentPkl}/reassign', [AdminSekolahController::class, 'reassignPlotting'])->name('adminsekolah.plotting.reassign');
    Route::delete('/plotting/{studentPkl}', [AdminSekolahController::class, 'destroyPlotting'])->name('adminsekolah.plotting.destroy');

    // Periode Pelaksanaan PKL
    Route::post('/periods', [AdminSekolahController::class, 'storePeriod'])->name('adminsekolah.periods.store');
    Route::put('/periods/{period}', [AdminSekolahController::class, 'updatePeriod'])->name('adminsekolah.periods.update');
    Route::post('/periods/{period}/toggle-active', [AdminSekolahController::class, 'togglePeriodActive'])->name('adminsekolah.periods.toggle-active');

    // Announcements / Pengumuman
    Route::get('/announcements', [AnnouncementController::class, 'adminIndex'])->name('adminsekolah.announcements');
    Route::post('/announcements', [AnnouncementController::class, 'adminStore'])->name('adminsekolah.announcements.store');
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'adminUpdate'])->name('adminsekolah.announcements.update');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'adminDestroy'])->name('adminsekolah.announcements.destroy');
    Route::post('/announcements/{announcement}/toggle-pin', [AnnouncementController::class, 'adminTogglePin'])->name('adminsekolah.announcements.toggle-pin');

    // Kalender Hari Libur
    Route::get('/holidays', [AdminSekolahController::class, 'holidays'])->name('adminsekolah.holidays');
    Route::post('/holidays', [AdminSekolahController::class, 'storeHoliday'])->name('adminsekolah.holidays.store');
    Route::delete('/holidays/{holiday}', [AdminSekolahController::class, 'destroyHoliday'])->name('adminsekolah.holidays.destroy');
    Route::post('/holidays/sync-national', [AdminSekolahController::class, 'syncNationalHolidays'])->name('adminsekolah.holidays.sync-national');

    // Laporan Kendala & SOS Siswa
    Route::get('/incidents', [AdminSekolahController::class, 'incidents'])->name('adminsekolah.incidents');
    Route::post('/incidents/{report}/followup', [AdminSekolahController::class, 'followupIncident'])->name('adminsekolah.incidents.followup');

    // E-Sertifikat & Transkrip PKL
    Route::get('/certificates', [AdminSekolahController::class, 'certificates'])->name('adminsekolah.certificates');
    Route::post('/certificates/generate', [AdminSekolahController::class, 'generateCertificate'])->name('adminsekolah.certificates.generate');
    Route::post('/certificates/batch', [AdminSekolahController::class, 'generateBatchCertificates'])->name('adminsekolah.certificates.batch');
    Route::get('/certificates/{certificate}/download', [AdminSekolahController::class, 'downloadCertificate'])->name('adminsekolah.certificates.download');

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
    Route::get('/billing', [AdminSekolahController::class, 'billing'])->name('adminsekolah.billing');
    Route::get('/invoices/{invoice}/pdf', [AdminSekolahController::class, 'downloadInvoicePdf'])->name('adminsekolah.invoices.pdf');
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
});

// 4. Guru Pembimbing Routes
Route::middleware(['auth', 'IdentifyTenant', 'role:guru-pembimbing'])->prefix('guru')->group(function () {
    Route::get('/dashboard', [TeacherController::class, 'dashboard'])->name('guru.dashboard');
    Route::get('/monitoring', [TeacherController::class, 'monitoring'])->name('guru.monitoring');
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
