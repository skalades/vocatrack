<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    use HasFactory, BelongsToSchool;

    protected $table = 'classes';

    protected $fillable = [
        'school_id',
        'major_id',
        'homeroom_teacher_id',
        'name',
        'grade_level',
        'academic_year',
    ];

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    /**
     * Dapatkan tingkat kelas berikutnya berdasarkan lama program keahlian.
     */
    public function getNextGradeLevel(): ?string
    {
        return match ($this->grade_level) {
            'X' => 'XI',
            'XI' => 'XII',
            'XII' => ($this->major?->isFourYearProgram() ? 'XIII' : null),
            default => null,
        };
    }

    /**
     * Generate nama rombel kelas berikutnya secara otomatis mengikuti pola penamaan saat ini.
     * Contoh: "XII GP 1" -> "XIII GP 1", "XI RPL 2" -> "XII RPL 2"
     */
    public function generateNextClassName(): ?string
    {
        $nextGrade = $this->getNextGradeLevel();
        if (!$nextGrade) {
            return null;
        }

        $currentGrade = $this->grade_level;

        // Gunakan regex penggantian batas kata agar tidak salah mengganti bagian nama lain
        // Misal mengganti "XII" dengan "XIII", atau "XI" dengan "XII"
        $pattern = '/\b' . preg_quote($currentGrade, '/') . '\b/';
        if (preg_match($pattern, $this->name)) {
            return preg_replace($pattern, $nextGrade, $this->name, 1);
        }

        return "{$nextGrade} {$this->name}";
    }
}
