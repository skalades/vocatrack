<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\IncidentReport;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\StudentPkl;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PklMapService
{
    /**
     * Dapatkan data spasial peta sebaran DU/DI dan status kehadiran siswa harian.
     * Menggunakan eager loading dan agregasi dalam memori untuk skalabilitas tinggi.
     */
    public function getMapData(int $schoolId, array $filters = [], ?int $teacherId = null): array
    {
        $date = $filters['date'] ?? Carbon::today()->toDateString();
        $isToday = ($date === Carbon::today()->toDateString());
        $isPast = ($date < Carbon::today()->toDateString());

        // Ambil batas waktu alfa sekolah
        $checkinAlfaTime = SchoolSetting::where('school_id', $schoolId)
            ->where('key', 'checkin_alfa')
            ->value('value') ?? '11:00:00';
        $currentTime = Carbon::now()->format('H:i:s');
        $isPastAlfaCutoff = $isToday ? ($currentTime >= $checkinAlfaTime) : $isPast;

        // Query Perusahaan milik sekolah
        $companyQuery = Company::where('school_id', $schoolId);

        // Pencarian nama perusahaan atau alamat
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $companyQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        // Eager load penempatan PKL aktif dan relasinya
        $companies = $companyQuery->with([
            'studentPkls' => function ($q) use ($teacherId, $filters) {
                $q->where('status', 'active');
                if ($teacherId && ($filters['scope'] ?? 'bimbingan') === 'bimbingan') {
                    $q->where('teacher_id', $teacherId);
                }
            },
            'studentPkls.student.user.profile',
            'studentPkls.student.class.major',
            'studentPkls.teacher.user',
            'studentPkls.attendances' => function ($q) use ($date) {
                $q->whereDate('date', $date);
            },
        ])->get();

        // Ambil ID semua studentPkl untuk query insiden/SOS terbuka sekaligus (Mencegah N+1)
        $allSpklIds = $companies->flatMap(fn($c) => $c->studentPkls->pluck('id'))->unique()->values()->all();

        $openIncidentsMap = IncidentReport::where('school_id', $schoolId)
            ->whereIn('student_pkl_id', $allSpklIds)
            ->whereIn('status', ['submitted', 'in_progress'])
            ->get()
            ->groupBy('student_pkl_id');

        $markers = [];
        $unmappedCompanies = [];

        $totalPlottedStudents = 0;
        $greenCount = 0;
        $yellowCount = 0;
        $redCount = 0;
        $grayCount = 0;

        foreach ($companies as $company) {
            $hasCoords = !is_null($company->latitude) && !is_null($company->longitude)
                && ($company->latitude != 0 || $company->longitude != 0);

            // Filter siswa berdasarkan Jurusan dan Rombel jika diterapkan
            $spkls = $company->studentPkls;

            if (!empty($filters['major_id']) && $filters['major_id'] !== 'all') {
                $spkls = $spkls->filter(fn($s) => $s->student?->class?->major_id == $filters['major_id']);
            }

            if (!empty($filters['class_id']) && $filters['class_id'] !== 'all') {
                $spkls = $spkls->filter(fn($s) => $s->student?->class_id == $filters['class_id']);
            }

            // Jika ada filter aktif dan perusahaan tidak memiliki siswa yang cocok, lewati
            $hasActiveFilter = (!empty($filters['major_id']) && $filters['major_id'] !== 'all')
                || (!empty($filters['class_id']) && $filters['class_id'] !== 'all')
                || ($teacherId && ($filters['scope'] ?? 'bimbingan') === 'bimbingan');

            if ($hasActiveFilter && $spkls->isEmpty()) {
                continue;
            }

            if (!$hasCoords) {
                $unmappedCompanies[] = [
                    'id' => $company->id,
                    'name' => $company->name,
                    'address' => $company->address,
                    'student_count' => $spkls->count(),
                ];
                continue;
            }

            // Hitung status kehadiran siswa di DU/DI ini
            $totalStudents = $spkls->count();
            $totalPlottedStudents += $totalStudents;

            $hadirCount = 0;
            $terlambatCount = 0;
            $izinSakitCount = 0;
            $alfaCount = 0;
            $belumAbsenCount = 0;
            $companyHasOpenIncident = false;
            $incidentSubjects = [];

            $studentsList = [];

            foreach ($spkls as $spkl) {
                $att = $spkl->attendances->first();
                $incidents = $openIncidentsMap->get($spkl->id) ?? collect();

                $studentHasIncident = $incidents->isNotEmpty();
                if ($studentHasIncident) {
                    $companyHasOpenIncident = true;
                    foreach ($incidents as $inc) {
                        $incidentSubjects[] = $inc->subject;
                    }
                }

                // Tentukan status kehadiran siswa
                if ($att) {
                    $status = $att->status;
                    $checkinTime = $att->checkin_time ? Carbon::parse($att->checkin_time)->format('H:i') : '-';
                    $isWithinRadius = $att->is_within_radius;
                } else {
                    $checkinTime = '-';
                    $isWithinRadius = null;
                    if ($isPast) {
                        $status = 'alfa';
                    } elseif ($isToday) {
                        $status = $isPastAlfaCutoff ? 'alfa' : 'belum_absen';
                    } else {
                        $status = 'belum_mulai';
                    }
                }

                // Klasifikasikan statistik
                match ($status) {
                    'hadir', 'dinas_luar', 'luar_kantor' => $hadirCount++,
                    'terlambat' => $terlambatCount++,
                    'izin', 'sakit' => $izinSakitCount++,
                    'alfa' => $alfaCount++,
                    default => $belumAbsenCount++,
                };

                $studentsList[] = [
                    'id' => $spkl->student_id,
                    'name' => $spkl->student->user->name ?? 'Siswa',
                    'nisn' => $spkl->student->nisn ?? '-',
                    'class_name' => $spkl->student->class->name ?? '-',
                    'major_name' => $spkl->student->class->major->name ?? '-',
                    'teacher_name' => $spkl->teacher->user->name ?? 'Belum Ditugaskan',
                    'status' => $status,
                    'checkin_time' => $checkinTime,
                    'is_within_radius' => $isWithinRadius,
                    'has_incident' => $studentHasIncident,
                ];
            }

            // Tentukan status pin indikator warna (Hijau, Kuning, Merah, Abu-Abu)
            if ($totalStudents === 0) {
                $pinStatus = 'gray';
                $pinLabel = 'Belum Ada Siswa Aktif';
                $pinDetail = 'Tidak ada penugasan siswa aktif di industri ini.';
                $pinColor = '#94a3b8'; // Slate-400
                $grayCount++;
            } elseif ($alfaCount > 0 || $companyHasOpenIncident) {
                $pinStatus = 'red';
                $pinLabel = 'Kritis / Ada Kendala';
                $reasons = [];
                if ($alfaCount > 0) $reasons[] = "{$alfaCount} Siswa Alfa";
                if ($companyHasOpenIncident) $reasons[] = count($incidentSubjects) . " Kendala/SOS Terbuka";
                $pinDetail = implode(' & ', $reasons);
                $pinColor = '#ef4444'; // Rose-500
                $redCount++;
            } elseif ($terlambatCount > 0 || $izinSakitCount > 0 || $belumAbsenCount > 0) {
                $pinStatus = 'yellow';
                $pinLabel = 'Perlu Perhatian';
                $reasons = [];
                if ($terlambatCount > 0) $reasons[] = "{$terlambatCount} Terlambat";
                if ($izinSakitCount > 0) $reasons[] = "{$izinSakitCount} Izin/Sakit";
                if ($belumAbsenCount > 0) $reasons[] = "{$belumAbsenCount} Belum Presensi";
                $pinDetail = implode(', ', $reasons);
                $pinColor = '#f59e0b'; // Amber-500
                $yellowCount++;
            } else {
                $pinStatus = 'green';
                $pinLabel = 'Aman & Tertib';
                $pinDetail = "Seluruh siswa ({$hadirCount} siswa) hadir tepat waktu.";
                $pinColor = '#10b981'; // Emerald-500
                $greenCount++;
            }

            // Filter status pin jika diminta
            if (!empty($filters['status_filter']) && $filters['status_filter'] !== 'all') {
                if ($filters['status_filter'] !== $pinStatus) {
                    continue;
                }
            }

            $markers[] = [
                'id' => $company->id,
                'name' => $company->name,
                'address' => $company->address ?? 'Alamat belum diatur',
                'contact_person' => $company->contact_person ?? '-',
                'phone' => $company->phone ?? '-',
                'lat' => (float) $company->latitude,
                'lng' => (float) $company->longitude,
                'radius' => (int) ($company->radius_meters ?: 100),
                'status' => $pinStatus,
                'status_label' => $pinLabel,
                'status_detail' => $pinDetail,
                'status_color' => $pinColor,
                'total_students' => $totalStudents,
                'hadir_count' => $hadirCount,
                'terlambat_count' => $terlambatCount,
                'izin_sakit_count' => $izinSakitCount,
                'alfa_count' => $alfaCount,
                'belum_absen_count' => $belumAbsenCount,
                'has_incident' => $companyHasOpenIncident,
                'students' => $studentsList,
                'gmaps_url' => "https://www.google.com/maps/dir/?api=1&destination={$company->latitude},{$company->longitude}",
            ];
        }

        return [
            'markers' => $markers,
            'unmapped_companies' => $unmappedCompanies,
            'stats' => [
                'total_mapped' => count($markers),
                'total_unmapped' => count($unmappedCompanies),
                'total_students' => $totalPlottedStudents,
                'green_count' => $greenCount,
                'yellow_count' => $yellowCount,
                'red_count' => $redCount,
                'gray_count' => $grayCount,
            ],
            'selected_date' => $date,
        ];
    }

    /**
     * Dapatkan opsi dropdown Jurusan dan Rombel untuk filter peta.
     */
    public function getFilterOptions(int $schoolId, ?int $teacherId = null): array
    {
        $majors = Major::where('school_id', $schoolId)->orderBy('name')->get();
        $classes = SchoolClass::where('school_id', $schoolId)->with('major')->orderBy('name')->get();

        return [
            'majors' => $majors,
            'classes' => $classes,
        ];
    }
}
