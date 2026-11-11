<?php

use App\Http\Controllers\AdminSekolahController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KepsekController;
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
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 1. Super Admin Platform Routes
Route::middleware(['auth', 'role:super-admin'])->prefix('super-admin')->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('superadmin.dashboard');
    Route::get('/schools', [SuperAdminController::class, 'schools'])->name('superadmin.schools');
    Route::post('/schools', [SuperAdminController::class, 'storeSchool'])->name('superadmin.schools.store');
    Route::post('/schools/{school}/toggle', [SuperAdminController::class, 'toggleSchool'])->name('superadmin.schools.toggle');
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
    Route::post('/classes', [AdminSekolahController::class, 'storeClass'])->name('adminsekolah.classes.store');

    // Students & Excel Import
    Route::get('/students', [AdminSekolahController::class, 'students'])->name('adminsekolah.students');
    Route::post('/students', [AdminSekolahController::class, 'storeStudent'])->name('adminsekolah.students.store');
    Route::post('/students/import', [AdminSekolahController::class, 'importStudents'])->name('adminsekolah.students.import');

    // Teachers
    Route::get('/teachers', [AdminSekolahController::class, 'teachers'])->name('adminsekolah.teachers');
    Route::post('/teachers', [AdminSekolahController::class, 'storeTeacher'])->name('adminsekolah.teachers.store');

    // DU/DI Companies & Supervisors
    Route::get('/companies', [AdminSekolahController::class, 'companies'])->name('adminsekolah.companies');
    Route::post('/companies', [AdminSekolahController::class, 'storeCompany'])->name('adminsekolah.companies.store');
    Route::put('/companies/{company}', [AdminSekolahController::class, 'updateCompany'])->name('adminsekolah.companies.update');

    // Plotting PKL
    Route::get('/plotting', [AdminSekolahController::class, 'plotting'])->name('adminsekolah.plotting');
    Route::post('/plotting', [AdminSekolahController::class, 'storePlotting'])->name('adminsekolah.plotting.store');

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
});

// 3. Siswa (Mobile-First) Routes
Route::middleware(['auth', 'IdentifyTenant', 'role:siswa'])->prefix('siswa')->group(function () {
    Route::get('/beranda', [StudentController::class, 'home'])->name('student.home');
    Route::get('/absensi', [StudentController::class, 'attendance'])->name('student.attendance');
    Route::post('/checkin', [StudentController::class, 'checkin'])->middleware('throttle:15,1')->name('student.checkin');
    Route::post('/checkout', [StudentController::class, 'checkout'])->middleware('throttle:15,1')->name('student.checkout');
    Route::post('/permohonan', [StudentController::class, 'storeRequest'])->name('student.request.store');

    Route::get('/jurnal', [StudentController::class, 'journals'])->name('student.journals');
    Route::post('/jurnal', [StudentController::class, 'storeJournal'])->name('student.journal.store');
    Route::post('/jurnal/{journal}/submit', [StudentController::class, 'submitJournal'])->name('student.journal.submit');

    Route::get('/profil', [StudentController::class, 'profile'])->name('student.profile');

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
    Route::post('/requests/{attRequest}/process', [TeacherController::class, 'processRequest'])->name('guru.requests.process');

    Route::get('/journals', [TeacherController::class, 'journals'])->name('guru.journals');
    Route::post('/journals/{journal}/process', [TeacherController::class, 'processJournal'])->name('guru.journals.process');

    Route::get('/assessments', [TeacherController::class, 'assessments'])->name('guru.assessments');
    Route::post('/assessments', [TeacherController::class, 'storeAssessment'])->name('guru.assessments.store');

    Route::get('/reports', [TeacherController::class, 'reports'])->name('guru.reports');
    Route::get('/reports/{spkl}/download', [TeacherController::class, 'downloadPdf'])->name('guru.reports.download');

    // Announcements / Pengumuman Guru
    Route::get('/announcements', [AnnouncementController::class, 'guruIndex'])->name('guru.announcements');
    Route::post('/announcements', [AnnouncementController::class, 'guruStore'])->name('guru.announcements.store');
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'guruUpdate'])->name('guru.announcements.update');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'guruDestroy'])->name('guru.announcements.destroy');
});

// 5. Pembimbing DU/DI Routes
Route::middleware(['auth', 'IdentifyTenant', 'role:pembimbing-dudi'])->prefix('dudi')->group(function () {
    Route::get('/dashboard', [SupervisorController::class, 'dashboard'])->name('dudi.dashboard');
    Route::get('/attendances', [SupervisorController::class, 'attendances'])->name('dudi.attendances');
    Route::get('/requests', [SupervisorController::class, 'requests'])->name('dudi.requests');
    Route::post('/requests/{attRequest}/process', [SupervisorController::class, 'processRequest'])->name('dudi.requests.process');

    Route::get('/journals', [SupervisorController::class, 'journals'])->name('dudi.journals');
    Route::post('/journals/{journal}/process', [SupervisorController::class, 'processJournal'])->name('dudi.journals.process');

    Route::get('/assessments', [SupervisorController::class, 'assessments'])->name('dudi.assessments');
    Route::post('/assessments', [SupervisorController::class, 'storeAssessment'])->name('dudi.assessments.store');
});

// 6. Kepala Sekolah Routes
Route::middleware(['auth', 'IdentifyTenant', 'role:kepala-sekolah'])->prefix('kepala-sekolah')->group(function () {
    Route::get('/dashboard', [KepsekController::class, 'dashboard'])->name('kepsek.dashboard');
    Route::get('/rekap', [KepsekController::class, 'rekap'])->name('kepsek.rekap');
    Route::get('/rekap/export', [KepsekController::class, 'exportRekap'])->name('kepsek.rekap.export');
});
