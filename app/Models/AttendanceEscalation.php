<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceEscalation extends Model
{
    use HasFactory, BelongsToSchool;

    public const TYPE_LABELS = [
        'checkin' => 'Belum Absen Masuk',
        'checkout' => 'Belum Absen Pulang',
        'journal' => 'Belum Isi Jurnal',
    ];

    protected $fillable = [
        'school_id',
        'student_pkl_id',
        'teacher_id',
        'escalated_by_user_id',
        'date',
        'missing_types',
        'note',
        'sent_in_app',
        'sent_whatsapp',
    ];

    protected $casts = [
        'date' => 'date',
        'missing_types' => 'array',
        'sent_in_app' => 'boolean',
        'sent_whatsapp' => 'boolean',
    ];

    public function studentPkl(): BelongsTo
    {
        return $this->belongsTo(StudentPkl::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by_user_id');
    }

    /**
     * Daftar label pelanggaran/kekurangan harian yang mudah dibaca.
     *
     * @return array<int, string>
     */
    public function getMissingLabelsAttribute(): array
    {
        return array_values(array_map(
            fn (string $type) => self::TYPE_LABELS[$type] ?? $type,
            (array) ($this->missing_types ?? [])
        ));
    }
}
