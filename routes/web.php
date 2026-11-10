<?php

use App\Http\Controllers\AdminSekolahController;
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
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
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

    // Plotting PKL
    Route::get('/plotting', [AdminSekolahController::class, 'plotting'])->name('adminsekolah.plotting');
    Route::post('/plotting', [AdminSekolahController::class, 'storePlotting'])->name('adminsekolah.plotting.store');
});

// 3. Siswa (Mobile-First) Routes
Route::middleware(['auth', 'IdentifyTenant', 'role:siswa'])->prefix('siswa')->group(function () {
    Route::get('/beranda', [StudentController::class, 'home'])->name('student.home');
    Route::get('/absensi', [StudentController::class, 'attendance'])->name('student.attendance');
    Route::post('/checkin', [StudentController::class, 'checkin'])->name('student.checkin');
    Route::post('/checkout', [StudentController::class, 'checkout'])->name('student.checkout');
    Route::post('/permohonan', [StudentController::class, 'storeRequest'])->name('student.request.store');

    Route::get('/jurnal', [StudentController::class, 'journals'])->name('student.journals');
    Route::post('/jurnal', [StudentController::class, 'storeJournal'])->name('student.journal.store');
    Route::post('/jurnal/{journal}/submit', [StudentController::class, 'submitJournal'])->name('student.journal.submit');

    Route::get('/profil', [StudentController::class, 'profile'])->name('student.profile');
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
    Route::get('/dashboard', [KepsekController::class, 'dashboard'])->name('kepapsekolah.dashboard');
    Route::get('/rekap', [KepsekController::class, 'rekap'])->name('kepapsekolah.rekap');
    Route::get('/rekap/export', [KepsekController::class, 'exportRekap'])->name('kepapsekolah.rekap.export');
});
