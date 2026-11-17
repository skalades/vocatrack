<?php

namespace App\Jobs;

use App\Services\CertificateService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateStudentCertificate implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public int $studentPklId) {}

    public function handle(CertificateService $certificateService): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $certificateService->generateForStudentPkl(
            \App\Models\StudentPkl::withoutGlobalScopes()->findOrFail($this->studentPklId)
        );
    }
}
