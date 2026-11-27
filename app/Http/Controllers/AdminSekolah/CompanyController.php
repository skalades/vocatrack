<?php

namespace App\Http\Controllers\AdminSekolah;

use App\Http\Requests\AdminSekolah\StoreCompanyRequest;
use App\Http\Requests\AdminSekolah\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\Major;
use App\Models\Profile;
use App\Models\StudentPkl;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CompanyController extends BaseAdminSekolahController
{
    public function companies(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $query = Company::where('school_id', $schoolId)
            ->with([
                'supervisors.user.profile',
                'studentPkls.student.user.profile',
                'studentPkls.student.class.major',
                'studentPkls.teacher.user.profile',
                'studentPkls.supervisor.user',
                'majors',
            ]);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhereHas('studentPkls.teacher.user', fn($tq) => $tq->where('name', 'like', "%{$search}%"));
            });
        }

        $selectedMajor = $request->query('major');
        if ($selectedMajor && $selectedMajor !== 'all') {
            if ($selectedMajor === 'lintas') {
                $query->doesntHave('majors');
            } else {
                $query->whereHas('majors', fn($q) => $q->where('majors.id', $selectedMajor));
            }
        }

        $companies = $query->latest()->paginate(10)->withQueryString();
        $majors = Major::where('school_id', $schoolId)->orderBy('name')->get();
        $allTeachers = Teacher::whereHas('user', fn($q) => $q->where('school_id', $schoolId))
            ->with(['user.profile', 'majors'])
            ->get()
            ->sortBy('user.name')
            ->values();

        return view('adminsekolah.companies', compact('companies', 'majors', 'selectedMajor', 'allTeachers'));
    }

    public function storeCompany(StoreCompanyRequest $request)
    {
        $schoolId = $this->getSchoolId();

        DB::transaction(function () use ($request, $schoolId) {
            $schedule = Company::extractSchedulePayload($request);

            $company = Company::create(array_merge([
                'school_id' => $schoolId,
                'name' => $request->name,
                'address' => $request->address,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'default_start_date' => $request->default_start_date,
                'default_end_date' => $request->default_end_date,
                'requires_industry_approval' => $request->boolean('requires_industry_approval'),
                'is_pkl_started' => $request->boolean('is_pkl_started'),
                'gps_enabled' => $request->boolean('gps_enabled'),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'radius_meters' => $request->radius_meters ?? 100,
            ], $schedule));

            if ($request->has('major_ids')) {
                $company->majors()->sync($request->major_ids);
            }

            if ($request->filled('supervisor_email')) {
                $user = User::create([
                    'school_id' => $schoolId,
                    'name' => $request->supervisor_name ?? $request->contact_person,
                    'email' => $request->supervisor_email,
                    'password' => Hash::make('password'),
                    'role' => 'pembimbing-dudi',
                    'is_active' => true,
                ]);

                Profile::create([
                    'user_id' => $user->id,
                    'gender' => 'L',
                ]);

                Supervisor::create([
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'position' => $request->supervisor_position ?? 'Mentor Lapangan DU/DI',
                ]);
            }
        });

        return back()->with('success', 'Data Mitra DU/DI berhasil ditambahkan.');
    }

    public function updateCompany(UpdateCompanyRequest $request, Company $company)
    {
        $schoolId = $this->getSchoolId();
        abort_if($company->school_id !== $schoolId, 403);

        DB::transaction(function () use ($request, $company) {
            $oldDefaultStart = $company->default_start_date?->toDateString();
            $oldDefaultEnd = $company->default_end_date?->toDateString();

            $schedule = Company::extractSchedulePayload($request);

            $company->update(array_merge([
                'name' => $request->name,
                'address' => $request->address,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'default_start_date' => $request->default_start_date,
                'default_end_date' => $request->default_end_date,
                'requires_industry_approval' => $request->boolean('requires_industry_approval'),
                'is_pkl_started' => $request->boolean('is_pkl_started'),
                'gps_enabled' => $request->boolean('gps_enabled'),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'radius_meters' => $request->radius_meters ?? 100,
            ], $schedule));

            $newDefaultStart = $company->default_start_date?->toDateString();
            $newDefaultEnd = $company->default_end_date?->toDateString();

            // Sinkronkan penempatan aktif yang masih memakai tanggal default perusahaan sebelumnya
            if ($oldDefaultStart !== $newDefaultStart || $oldDefaultEnd !== $newDefaultEnd) {
                $company->studentPkls()
                    ->where('status', 'active')
                    ->get()
                    ->each(function (StudentPkl $spkl) use ($oldDefaultStart, $oldDefaultEnd, $newDefaultStart, $newDefaultEnd) {
                        $updates = [];
                        $curStart = $spkl->start_date?->toDateString();
                        $curEnd = $spkl->end_date?->toDateString();

                        if ($oldDefaultStart !== $newDefaultStart && ($curStart === null || $curStart === $oldDefaultStart)) {
                            $updates['start_date'] = $newDefaultStart;
                        }
                        if ($oldDefaultEnd !== $newDefaultEnd && ($curEnd === null || $curEnd === $oldDefaultEnd)) {
                            $updates['end_date'] = $newDefaultEnd;
                        }

                        if (!empty($updates)) {
                            $spkl->update($updates);
                        }
                    });
            }

            $company->majors()->sync($request->major_ids ?? []);
        });

        return back()->with('success', 'Data Mitra DU/DI berhasil diperbarui.');
    }

    public function destroyCompany(Company $company)
    {
        $schoolId = $this->getSchoolId();
        abort_if($company->school_id !== $schoolId, 403);

        if ($company->studentPkls()->exists()) {
            return back()->withErrors(['company' => 'Mitra DU/DI tidak dapat dihapus karena memiliki riwayat penugasan siswa PKL.']);
        }

        DB::transaction(function () use ($company) {
            $company->supervisors()->each(function ($sup) {
                $user = $sup->user;
                $sup->delete();
                if ($user) {
                    $user->profile()->delete();
                    $user->delete();
                }
            });
            $company->majors()->detach();
            $company->delete();
        });

        return back()->with('success', 'Data mitra DU/DI berhasil dihapus.');
    }

    public function storeCompanySupervisor(Request $request, Company $company)
    {
        $schoolId = $this->getSchoolId();
        abort_if($company->school_id !== $schoolId, 403);

        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($request, $schoolId, $company) {
            $user = User::create([
                'school_id' => $schoolId,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make('password'),
                'role' => 'pembimbing-dudi',
                'is_active' => true,
            ]);

            Profile::create([
                'user_id' => $user->id,
                'gender' => 'L',
                'phone' => $request->phone,
                'address' => $company->address,
            ]);

            Supervisor::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'position' => $request->position ?? 'Mentor Lapangan DU/DI',
            ]);
        });

        return back()->with('success', 'Mentor DU/DI berhasil ditambahkan untuk ' . $company->name);
    }

    public function updateCompanySupervisor(Request $request, Company $company, Supervisor $supervisor)
    {
        $schoolId = $this->getSchoolId();
        abort_if($company->school_id !== $schoolId || $supervisor->company_id !== $company->id, 403);
        abort_if($supervisor->user->school_id !== $schoolId, 403);

        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($supervisor->user_id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        DB::transaction(function () use ($request, $supervisor) {
            $user = $supervisor->user;
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
            ];
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }
            $user->update($userData);

            $supervisor->update([
                'position' => $request->position ?? 'Mentor Lapangan DU/DI',
            ]);

            $profile = $user->profile;
            if ($profile) {
                $profile->update([
                    'phone' => $request->phone,
                ]);
            } else {
                Profile::create([
                    'user_id' => $user->id,
                    'gender' => 'L',
                    'phone' => $request->phone,
                ]);
            }
        });

        return back()->with('success', 'Data mentor DU/DI ' . $request->name . ' berhasil diperbarui.');
    }

    public function destroyCompanySupervisor(Request $request, Company $company, Supervisor $supervisor)
    {
        $schoolId = $this->getSchoolId();
        abort_if($company->school_id !== $schoolId || $supervisor->company_id !== $company->id, 403);
        abort_if($supervisor->user->school_id !== $schoolId, 403);

        $hasStudents = $supervisor->studentPkls()->exists();
        $replacementId = $request->input('replacement_supervisor_id');
        $confirmDetach = $request->boolean('confirm_detach') || $replacementId === 'detach';

        if ($hasStudents && !$replacementId && !$confirmDetach) {
            return back()->withErrors(['supervisor' => 'Mentor tidak dapat dihapus karena masih ditugaskan pada siswa PKL aktif. Silakan pilih mentor pengganti atau lepas penugasan terlebih dahulu.']);
        }

        DB::transaction(function () use ($supervisor, $company, $hasStudents, $replacementId, $confirmDetach) {
            if ($hasStudents) {
                if ($replacementId && $replacementId !== 'detach') {
                    $replacement = Supervisor::where('id', $replacementId)
                        ->where('company_id', $company->id)
                        ->firstOrFail();

                    $supervisor->studentPkls()->update(['supervisor_id' => $replacement->id]);
                } elseif ($confirmDetach) {
                    $supervisor->studentPkls()->update(['supervisor_id' => null]);
                }
            }

            $user = $supervisor->user;
            $supervisor->delete();
            if ($user) {
                $user->profile()->delete();
                $user->delete();
            }
        });

        return back()->with('success', 'Akun mentor DU/DI berhasil dihapus.');
    }

    public function reassignCompanyBatch(Request $request, Company $company)
    {
        $schoolId = $this->getSchoolId();
        abort_if($company->school_id !== $schoolId, 403);

        $request->validate([
            'target_type' => ['required', 'in:teacher,supervisor,both'],
            'teacher_id' => [
                'nullable',
                'required_if:target_type,teacher',
                'required_if:target_type,both',
                Rule::exists('teachers', 'id')->where(function ($query) use ($schoolId) {
                    $query->whereIn('user_id', User::where('school_id', $schoolId)->select('id'));
                }),
            ],
            'supervisor_id' => [
                'nullable',
                Rule::exists('supervisors', 'id')->where('company_id', $company->id),
            ],
            'filter_teacher_id' => ['nullable', 'integer'],
            'filter_supervisor_id' => ['nullable', 'integer'],
            'student_pkl_ids' => ['nullable', 'array'],
            'student_pkl_ids.*' => [Rule::exists('student_pkl', 'id')->where('company_id', $company->id)],
        ]);

        $query = StudentPkl::where('company_id', $company->id)
            ->where('school_id', $schoolId);

        if ($request->filled('student_pkl_ids')) {
            $query->whereIn('id', $request->student_pkl_ids);
        }

        if ($request->filled('filter_teacher_id')) {
            $query->where('teacher_id', $request->filter_teacher_id);
        }

        if ($request->filled('filter_supervisor_id')) {
            $query->where('supervisor_id', $request->filter_supervisor_id);
        }

        $updates = [];
        if (in_array($request->target_type, ['teacher', 'both']) && $request->filled('teacher_id')) {
            $updates['teacher_id'] = $request->teacher_id;
        }

        if (in_array($request->target_type, ['supervisor', 'both'])) {
            $updates['supervisor_id'] = $request->filled('supervisor_id') ? $request->supervisor_id : null;
        }

        if (empty($updates)) {
            return back()->withErrors(['batch' => 'Tidak ada perubahan pembimbing yang dipilih.']);
        }

        $count = $query->update($updates);

        return back()->with('success', "Berhasil memperbarui pembimbing untuk {$count} siswa di {$company->name}.");
    }
}
