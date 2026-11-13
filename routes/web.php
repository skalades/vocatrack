<?php

use App\Http\Controllers\AdminSekolahController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\KepsekController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ParentLinkController;
use App\Http\Controllers\ParentMonitoringController;
use App\Http\Controllers\PklMapController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

// Guest Public Verification for E-Sertifikat PKL (QR Code Destination)
Route::get('/verify/cert/{uuid}', [CertificateVerificationController::class, 'verify'])->middleware('throttle:60,1')->name('public.cert.verify');
Route::get('/verify/cert/{uuid}/download', [CertificateVerificationController::class, 'download'])->middleware('throttle:30,1')->name('public.cert.download');

// Guest Public Parent Monitoring Portal (No Login Required)
Route::get('/pantau/{token}', [ParentMonitoringController::class, 'show'])->middleware('throttle:60,1')->name('parent.monitor');

// Shared Authenticated Notification Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

    // Impersonation Routes
    Route::post('/impersonate/leave', [ImpersonationController::class, 'stop'])->name('impersonate.leave');
    Route::post('/impersonate/{user}', [ImpersonationController::class, 'start'])->name('impersonate.start');
});

// 1. Super Admin Platform Routes
Route::middleware(['auth', 'role:super-admin'])->prefix('super-admin')->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('superadmin.dashboard');
    Route::get('/schools', [SuperAdminController::class, 'schools'])->name('superadmin.schools');
    Route::post('/schools', [SuperAdminController::class, 'storeSchool'])->name('superadmin.schools.store');
    Route::post('/schools/{school}/toggle', [SuperAdminController::class, 'toggleSchool'])->name('superadmin.schools.toggle');
    Route::post('/schools/{school}/extend', [SuperAdminController::class, 'extendSubscription'])->name('superadmin.schools.extend');
    Route::post('/schools/{school}/impersonate', [SuperAdminController::class, 'impersonateSchool'])->name('superadmin.schools.impersonate');

    // SaaS Invoices & Billing
    Route::get('/invoices', [SuperAdminController::class, 'invoices'])->name('superadmin.invoices');
    Route::post('/invoices', [SuperAdminController::class, 'storeInvoice'])->name('superadmin.invoices.store');
    Route::post('/invoices/{invoice}/verify', [SuperAdminController::class, 'markInvoicePaid'])->name('superadmin.invoices.verify');

    // SaaS Plans & Licensing
    Route::get('/plans', [SuperAdminController::class, 'plans'])->name('superadmin.plans');
    Route::post('/plans', [SuperAdminController::class, 'storePlan'])->name('superadmin.plans.store');
    Route::put('/plans/{plan}', [SuperAdminController::class, 'updatePlan'])->name('superadmin.plans.update');
    Route::delete('/plans/{plan}', [SuperAdminController::class, 'destroyPlan'])->name('superadmin.plans.destroy');

    // Platform Settings & System Logo
    Route::get('/settings', [SuperAdminController::class, 'settings'])->name('superadmin.settings');
    Route::post('/settings', [SuperAdminController::class, 'updateSettings'])->name('superadmin.settings.update');
});

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
    Route::delete('/students/{student}', [AdminSekolahController::class, 'destroyStudent'])->name('adminsekolah.students.destroy');
    Route::post('/students/import', [AdminSekolahController::class, 'importStudents'])->name('adminsekolah.students.import');

    // Teachers
    Route::get('/teachers', [AdminSekolahController::class, 'teachers'])->name('adminsekolah.teachers');
    Route::post('/teachers', [AdminSekolahController::class, 'storeTeacher'])->name('adminsekolah.teachers.store');
    Route::put('/teachers/{teacher}', [AdminSekolahController::class, 'updateTeacher'])->name('adminsekolah.teachers.update');
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
});

// 3. Siswa (Mobile-First) Routes
Route::middleware(['auth', 'IdentifyTenant', 'role:siswa'])->prefix('siswa')->group(function () {
    Route::get('/beranda', [StudentController::class, 'home'])->name('student.home');
    Route::get('/absensi', [StudentController::class, 'attendance'])->name('student.attendance');
    Route::post('/checkin', [StudentController::class, 'checkin'])->middleware('throttle:15,1')->name('student.checkin');
    Route::post('/checkout', [StudentController::class, 'checkout'])->middleware('throttle:15,1')->name('student.checkout');
    Route::post('/permohonan', [StudentController::class, 'storeRequest'])->name('student.request.store');
    Route::post('/lapor-kendala', [StudentController::class, 'storeIncident'])->name('student.incident.store');

    Route::get('/jurnal', [StudentController::class, 'journals'])->name('student.journals');
    Route::post('/jurnal', [StudentController::class, 'storeJournal'])->name('student.journal.store');
    Route::post('/jurnal/{journal}/submit', [StudentController::class, 'submitJournal'])->name('student.journal.submit');

    Route::get('/profil', [StudentController::class, 'profile'])->name('student.profile');
    Route::post('/parent-token/regenerate', [ParentMonitoringController::class, 'regenerate'])->name('student.parent-token.regenerate');
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
