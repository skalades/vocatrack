<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $totalSchools = School::count();
        $activeSchools = School::where('is_active', true)->count();
        $totalStudents = Student::count();
        $totalUsers = User::count();

        $recentSchools = School::withCount(['users', 'classes', 'companies'])
            ->latest()
            ->take(5)
            ->get();

        return view('superadmin.dashboard', compact(
            'totalSchools',
            'activeSchools',
            'totalStudents',
            'totalUsers',
            'recentSchools'
        ));
    }

    public function schools()
    {
        $schools = School::withCount(['users', 'classes', 'companies'])
            ->latest()
            ->paginate(10);

        return view('superadmin.schools', compact('schools'));
    }

    public function storeSchool(Request $request)
    {
        $request->validate([
            'npsn' => ['required', 'string', 'size:8', 'unique:schools,npsn'],
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:100'],
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_email' => ['required', 'email', 'unique:users,email'],
        ]);

        $school = School::create([
            'npsn' => $request->npsn,
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'is_active' => true,
        ]);

        // Default School Settings
        $defaultSettings = [
            'checkin_start' => '06:00:00',
            'checkin_late' => '08:00:00',
            'checkin_alfa' => '11:00:00',
            'checkout_start' => '16:00:00',
            'journal_backdate_limit' => '3',
            'weight_dudi' => '60',
            'weight_guru' => '40',
            'kop_header_line1' => 'PEMERINTAH PROVINSI',
            'kop_header_line2' => 'DINAS PENDIDIKAN',
            'kop_header_line3' => strtoupper($school->name),
            'kepsek_name' => 'Kepala Sekolah',
            'kepsek_nip' => '-',
        ];

        foreach ($defaultSettings as $k => $v) {
            SchoolSetting::create([
                'school_id' => $school->id,
                'key' => $k,
                'value' => $v,
            ]);
        }

        // Auto-create initial School Admin
        User::create([
            'school_id' => $school->id,
            'name' => $request->admin_name,
            'email' => $request->admin_email,
            'password' => Hash::make('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        return back()->with('success', "Sekolah {$school->name} dan akun admin sekolah berhasil dibuat dengan password awal: password");
    }

    public function toggleSchool(School $school)
    {
        $school->update(['is_active' => !$school->is_active]);

        $status = $school->is_active ? 'diaktifkan' : 'dinonaktifkan (seluruh sesi user diblokir)';
        return back()->with('success', "Status sekolah {$school->name} berhasil {$status}.");
    }
}
