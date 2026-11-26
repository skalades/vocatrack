<?php

namespace App\Exports;

use App\Exports\Sheets\KajurAccountsDataSheet;
use App\Exports\Sheets\KajurBroadcastWhatsappSheet;
use App\Exports\Sheets\WaliKelasAccountsDataSheet;
use App\Exports\Sheets\WaliKelasBroadcastWhatsappSheet;
use App\Models\Major;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LeadershipCredentialsExport implements WithMultipleSheets
{
    use Exportable;

    public const ROLE_ALL = 'all';
    public const ROLE_KAJUR = 'kajur';
    public const ROLE_WALIKELAS = 'walikelas';

    public function __construct(
        protected int $schoolId,
        protected array $filters = [],
        protected string $roleScope = self::ROLE_ALL
    ) {}

    public function sheets(): array
    {
        $school = School::find($this->schoolId);
        $schoolName = $school?->name ?? 'Sekolah';

        $majorId = $this->resolveFilteredMajorId();
        $majorFilterName = 'Semua Jurusan';
        if ($majorId !== null) {
            $major = Major::where('school_id', $this->schoolId)->find($majorId);
            if ($major) {
                $majorFilterName = $major->name;
            }
        }

        $meta = [
            'school_name' => $schoolName,
            'major_name' => $majorFilterName,
            'downloaded_at' => now()->translatedFormat('d F Y, H:i') . ' WIB',
        ];

        $sheets = [];

        if (in_array($this->roleScope, [self::ROLE_ALL, self::ROLE_KAJUR], true)) {
            $kajurs = $this->queryKajurs($majorId);
            $sheets[] = new KajurAccountsDataSheet($kajurs, $meta);
            $sheets[] = new KajurBroadcastWhatsappSheet($kajurs, $meta);
        }

        if (in_array($this->roleScope, [self::ROLE_ALL, self::ROLE_WALIKELAS], true)) {
            $waliKelas = $this->queryWaliKelas($majorId);
            $sheets[] = new WaliKelasAccountsDataSheet($waliKelas, $meta);
            $sheets[] = new WaliKelasBroadcastWhatsappSheet($waliKelas, $meta);
        }

        return $sheets;
    }

    protected function resolveFilteredMajorId(): ?int
    {
        $major = $this->filters['major'] ?? null;
        if (!$major || in_array($major, ['all', 'lintas'], true) || !is_numeric($major)) {
            return null;
        }

        return (int) $major;
    }

    protected function queryKajurs(?int $majorId): Collection
    {
        $query = Teacher::whereHas('user', fn (Builder $q) => $q->where('school_id', $this->schoolId))
            ->whereHas('headedMajors', function (Builder $q) use ($majorId): void {
                $q->where('school_id', $this->schoolId);
                if ($majorId !== null) {
                    $q->where('id', $majorId);
                }
            })
            ->with([
                'user.profile',
                'headedMajors' => function ($q) use ($majorId): void {
                    $q->where('school_id', $this->schoolId)->orderBy('code');
                    if ($majorId !== null) {
                        $q->where('id', $majorId);
                    }
                    $q->with(['classes' => fn ($cq) => $cq->withCount('students')]);
                },
            ]);

        $this->applySearch($query, true);

        return $query->get()
            ->sortBy(fn (Teacher $t) => mb_strtolower($t->user?->name ?? ''))
            ->values();
    }

    protected function queryWaliKelas(?int $majorId): Collection
    {
        $query = Teacher::whereHas('user', fn (Builder $q) => $q->where('school_id', $this->schoolId))
            ->whereHas('homeroomClasses', function (Builder $q) use ($majorId): void {
                $q->where('school_id', $this->schoolId);
                if ($majorId !== null) {
                    $q->where('major_id', $majorId);
                }
            })
            ->with([
                'user.profile',
                'homeroomClasses' => function ($q) use ($majorId): void {
                    $q->where('school_id', $this->schoolId)
                        ->with('major')
                        ->withCount('students')
                        ->orderBy('name');
                    if ($majorId !== null) {
                        $q->where('major_id', $majorId);
                    }
                },
            ]);

        $this->applySearch($query, false);

        return $query->get()
            ->sortBy(fn (Teacher $t) => mb_strtolower($t->homeroomClasses->first()?->name ?? $t->user?->name ?? ''))
            ->values();
    }

    protected function applySearch(Builder $query, bool $isKajur): void
    {
        $term = trim((string) ($this->filters['search'] ?? ''));
        if ($term === '') {
            return;
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtolower($term));
        $like = "%{$escaped}%";

        $query->where(function (Builder $q) use ($like, $isKajur): void {
            $q->whereHas('user', function (Builder $uq) use ($like): void {
                $uq->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$like])
                    ->orWhereRaw("LOWER(COALESCE(wa_number, '')) LIKE ?", [$like])
                    ->orWhereHas('profile', fn (Builder $pq) => $pq->whereRaw("LOWER(COALESCE(phone, '')) LIKE ?", [$like]));
            })
                ->orWhereRaw("LOWER(COALESCE(nip, '')) LIKE ?", [$like]);

            if ($isKajur) {
                $q->orWhereHas('headedMajors', function (Builder $mq) use ($like): void {
                    $mq->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(code) LIKE ?', [$like]);
                });
            } else {
                $q->orWhereHas('homeroomClasses', function (Builder $cq) use ($like): void {
                    $cq->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereHas('major', function (Builder $mq) use ($like): void {
                            $mq->whereRaw('LOWER(name) LIKE ?', [$like])
                                ->orWhereRaw('LOWER(code) LIKE ?', [$like]);
                        });
                });
            }
        });
    }
}
