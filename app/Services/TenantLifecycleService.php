<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionLifecycleNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Satu-satunya sumber kebenaran siklus hidup tenant sekolah:
 * Onboarding (trial) -> Aktif -> Akan Habis -> Masa Tenggang -> Kedaluwarsa, plus Suspend manual.
 *
 * Aturan inti:
 *  - Setiap periode langganan adalah baris baru di school_subscriptions (riwayat tidak ditimpa).
 *  - Periode baru HANYA lahir dari invoice aktivasi/perpanjangan/upgrade yang lunas.
 *  - Tahap selalu dihitung dari data (is_active + periode terakhir), tidak disimpan terpisah.
 */
class TenantLifecycleService
{
    public const GRACE_DAYS = 7;
    public const RENEWAL_NOTICE_DAYS = 30;
    public const REMINDER_DAYS = [7, 1];

    public const STAGES = [
        'trial' => [
            'label' => 'Trial',
            'color' => 'sky',
            'hint' => 'Masa uji coba gratis berjalan. Arahkan ke paket berbayar sebelum berakhir.',
        ],
        'active' => [
            'label' => 'Aktif',
            'color' => 'emerald',
            'hint' => 'Periode berbayar berjalan normal.',
        ],
        'expiring' => [
            'label' => 'Akan Habis',
            'color' => 'amber',
            'hint' => 'Masa sewa berakhir dalam 30 hari. Tagihan perpanjangan diterbitkan otomatis.',
        ],
        'grace' => [
            'label' => 'Masa Tenggang',
            'color' => 'orange',
            'hint' => 'Masa sewa sudah habis. Sekolah masih bisa dipakai maksimal 7 hari sambil menunggu pembayaran.',
        ],
        'expired' => [
            'label' => 'Kedaluwarsa',
            'color' => 'rose',
            'hint' => 'Masa tenggang lewat. Pengguna sekolah tidak bisa login sampai tagihan dilunasi.',
        ],
        'suspended' => [
            'label' => 'Ditangguhkan',
            'color' => 'slate',
            'hint' => 'Dinonaktifkan manual oleh Super Admin. Seluruh akses sekolah diblokir.',
        ],
        'no_plan' => [
            'label' => 'Belum Berlangganan',
            'color' => 'slate',
            'hint' => 'Sekolah belum memiliki periode langganan.',
        ],
    ];

    // ------------------------------------------------------------------
    // Tahap
    // ------------------------------------------------------------------

    public function stage(School $school, ?SchoolSubscription $subscription = null): string
    {
        if (!$school->is_active) {
            return 'suspended';
        }

        $subscription ??= $school->activeSubscription();
        if (!$subscription) {
            return 'no_plan';
        }

        $days = $subscription->daysRemaining();

        if ($days < 0) {
            return $days >= -self::GRACE_DAYS ? 'grace' : 'expired';
        }

        if ($subscription->status === 'trial') {
            return 'trial';
        }

        return $days <= self::RENEWAL_NOTICE_DAYS ? 'expiring' : 'active';
    }

    public static function stageMeta(string $stage): array
    {
        return self::STAGES[$stage] ?? self::STAGES['no_plan'];
    }

    /**
     * Hitung tahap banyak sekolah sekaligus (relasi subscriptions harus sudah di-eager load).
     *
     * @return Collection<int, string> [school_id => stage]
     */
    public function stagesFor(Collection $schools): Collection
    {
        return $schools->mapWithKeys(fn (School $s) => [$s->id => $this->stage($s)]);
    }

    /**
     * Tanggal terakhir sekolah masih bisa diakses (akhir masa tenggang).
     */
    public function accessEndsAt(SchoolSubscription $subscription): Carbon
    {
        return $subscription->end_date->copy()->addDays(self::GRACE_DAYS);
    }

    // ------------------------------------------------------------------
    // Tagihan & pembayaran
    // ------------------------------------------------------------------

    /**
     * Terbitkan invoice. Untuk tipe aktivasi/perpanjangan/upgrade, paket & durasi disimpan
     * di invoice dan baru diterapkan menjadi periode baru ketika invoice dilunasi.
     */
    public function issueInvoice(
        School $school,
        string $type,
        string $title,
        float $amount,
        ?SubscriptionPlan $plan = null,
        ?int $months = null,
        ?int $studentQuota = null,
        ?int $storageQuotaMb = null,
        ?string $notes = null,
        int $dueInDays = 14,
        ?Carbon $dueDate = null
    ): Invoice {
        return Invoice::create([
            'invoice_number' => $this->nextInvoiceNumber(),
            'school_id' => $school->id,
            'subscription_id' => $school->activeSubscription()?->id,
            'type' => $type,
            'plan_id' => $plan?->id,
            'period_months' => $months,
            'student_quota' => $studentQuota,
            'storage_quota_mb' => $storageQuotaMb,
            'title' => Str::limit($title, 150, ''),
            'amount' => $amount,
            'due_date' => $dueDate ?? Carbon::today()->addDays($dueInDays),
            'status' => 'unpaid',
            'notes' => $notes,
        ]);
    }

    /**
     * Catat pembayaran invoice. Bila invoice membawa periode, periode baru langsung diterapkan.
     */
    public function recordPayment(Invoice $invoice, ?string $paymentMethod = null): ?SchoolSubscription
    {
        if (!$invoice->isOpen()) {
            throw new \RuntimeException("Invoice {$invoice->invoice_number} tidak dalam status menunggu pembayaran.");
        }

        return DB::transaction(function () use ($invoice, $paymentMethod) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => Carbon::now(),
                'payment_method' => $paymentMethod ?: ($invoice->payment_method ?: 'Pembayaran manual (dicatat Super Admin)'),
            ]);

            $period = $invoice->createsPeriod() ? $this->applyInvoice($invoice) : null;

            AuditLogService::log(
                'invoice_verify',
                'billing',
                "Faktur {$invoice->invoice_number} ({$invoice->type_label}) senilai " . $invoice->formatted_amount . ' diverifikasi LUNAS.'
                    . ($period ? " Periode baru {$period->plan_name} berlaku s.d. {$period->end_date->format('d M Y')}." : ''),
                $invoice->school
            );

            if ($period) {
                $this->notifySchool(
                    $invoice->school,
                    'paid',
                    'Pembayaran diterima',
                    "Terima kasih. Langganan {$period->plan_name} aktif hingga {$period->end_date->format('d M Y')}.",
                    'emerald'
                );
            }

            return $period;
        });
    }

    /**
     * Ubah invoice lunas menjadi periode langganan baru (idempoten).
     */
    public function applyInvoice(Invoice $invoice): ?SchoolSubscription
    {
        if ($invoice->applied_at || !$invoice->createsPeriod()) {
            return null;
        }

        $school = $invoice->school;
        $current = $school->activeSubscription();
        $plan = $invoice->plan ?? $current?->plan;

        // Periode baru menyambung periode berjalan; trial yang dikonversi atau periode yang
        // sudah lewat dimulai hari ini.
        $start = Carbon::today();
        if ($current && $current->status !== 'trial' && $current->end_date->gt($start)) {
            $start = $current->end_date->copy();
        }

        if ($current && $current->status === 'trial' && $current->end_date->gte(Carbon::today())) {
            $current->update(['status' => 'converted', 'end_date' => Carbon::today()]);
        }

        $period = SchoolSubscription::create([
            'school_id' => $school->id,
            'plan_id' => $plan?->id,
            'plan_name' => $plan?->name ?? $current?->plan_name ?? 'Paket Kustom',
            'start_date' => $start,
            'end_date' => $start->copy()->addMonths($invoice->period_months),
            'price_paid' => $invoice->amount,
            'student_quota' => $invoice->student_quota ?? $plan?->max_students ?? $current?->student_quota ?? 0,
            'storage_quota_mb' => $invoice->storage_quota_mb ?? $plan?->max_storage_mb ?? $current?->storage_quota_mb ?? 1024,
            'status' => 'active',
            'notes' => "Dari invoice {$invoice->invoice_number}",
        ]);

        $invoice->update(['applied_at' => Carbon::now(), 'subscription_id' => $period->id]);

        return $period;
    }

    /**
     * Catat perpanjangan/upgrade dari panel Super Admin. Bila sudah dibayar, periode baru
     * langsung aktif; bila belum, tagihan diterbitkan dan periode menunggu pelunasan.
     *
     * @return array{invoice: Invoice, period: ?SchoolSubscription}
     */
    public function renew(
        School $school,
        SubscriptionPlan $plan,
        int $months,
        float $amount,
        bool $paid,
        ?string $paymentMethod = null,
        ?int $studentQuota = null,
        ?int $storageQuotaMb = null,
        ?string $notes = null
    ): array {
        $current = $school->activeSubscription();
        $type = (!$current || $current->status === 'trial')
            ? 'activation'
            : ($current->plan_id && $current->plan_id !== $plan->id ? 'upgrade' : 'renewal');

        $title = match ($type) {
            'activation' => "Aktivasi {$plan->name} ({$months} Bulan)",
            'upgrade' => "Upgrade ke {$plan->name} ({$months} Bulan)",
            default => "Perpanjangan Sewa {$plan->name} (+{$months} Bulan)",
        };

        return DB::transaction(function () use ($school, $type, $title, $amount, $plan, $months, $studentQuota, $storageQuotaMb, $notes, $paid, $paymentMethod) {
            // Tagihan periode lama yang masih terbuka digantikan oleh keputusan terbaru ini,
            // agar sekolah tidak pernah memegang dua tagihan perpanjangan sekaligus.
            Invoice::where('school_id', $school->id)
                ->whereIn('type', Invoice::PERIOD_TYPES)
                ->whereIn('status', ['unpaid', 'pending_verification'])
                ->get()
                ->each(fn (Invoice $old) => $old->update([
                    'status' => 'cancelled',
                    'notes' => trim(($old->notes ?? '') . ' [Digantikan tagihan baru]'),
                ]));

            $invoice = $this->issueInvoice($school, $type, $title, $amount, $plan, $months, $studentQuota, $storageQuotaMb, $notes, 7);
            if ($paymentMethod) {
                $invoice->update(['payment_method' => $paymentMethod]);
            }

            $period = null;
            if ($paid || $amount <= 0) {
                $period = $this->recordPayment($invoice->fresh(), $paymentMethod);
            } else {
                $this->notifySchool(
                    $school,
                    'invoice_issued',
                    'Tagihan baru diterbitkan',
                    "{$title} sebesar {$invoice->formatted_amount}, jatuh tempo {$invoice->due_date->format('d M Y')}.",
                    'amber'
                );
            }

            return ['invoice' => $invoice->fresh(), 'period' => $period];
        });
    }

    // ------------------------------------------------------------------
    // Evaluasi harian (dipanggil cron saas:evaluate-subscriptions)
    // ------------------------------------------------------------------

    /**
     * Sinkronkan status periode, terbitkan tagihan perpanjangan otomatis dan kirim pengingat.
     *
     * @return string tahap sekolah setelah evaluasi
     */
    public function evaluate(School $school): string
    {
        $subscription = $school->activeSubscription();
        $stage = $this->stage($school, $subscription);

        if (!$subscription || $stage === 'suspended') {
            return $stage;
        }

        $this->syncStatus($school, $subscription, $stage);

        if (in_array($stage, ['expiring', 'grace'], true)) {
            $this->ensureRenewalInvoice($school, $subscription);
        }

        $days = $subscription->daysRemaining();
        if (in_array($days, self::REMINDER_DAYS, true)) {
            $what = $stage === 'trial' ? 'Masa trial' : 'Masa sewa';
            $this->notifySchool(
                $school,
                'reminder',
                "{$what} berakhir {$days} hari lagi",
                "{$what} {$school->name} berakhir pada {$subscription->end_date->format('d M Y')}. "
                    . ($stage === 'trial' ? 'Pilih paket berbayar agar layanan tidak terputus.' : 'Segera lunasi tagihan perpanjangan.'),
                'amber'
            );
        }

        return $stage;
    }

    protected function syncStatus(School $school, SchoolSubscription $subscription, string $stage): void
    {
        $target = match ($stage) {
            'grace' => 'grace_period',
            'expired' => 'expired',
            default => null,
        };

        if (!$target || $subscription->status === $target || $subscription->status === 'suspended') {
            return;
        }

        $subscription->update(['status' => $target]);

        if ($target === 'grace_period') {
            AuditLogService::log('subscription_grace_period', 'billing',
                "Masa sewa {$school->name} habis. Masuk masa tenggang " . self::GRACE_DAYS . ' hari.', $school);
            $this->notifySchool($school, 'grace', 'Masa tenggang dimulai',
                "Masa sewa {$school->name} telah berakhir. Akses masih dibuka hingga "
                    . $this->accessEndsAt($subscription)->format('d M Y') . ' sambil menunggu pembayaran.', 'rose');
        } else {
            AuditLogService::log('subscription_expired', 'billing',
                "Masa tenggang {$school->name} berakhir. Akses pengguna sekolah diblokir.", $school);
            $this->notifySchool($school, 'expired', 'Langganan kedaluwarsa',
                "Akses {$school->name} ditutup karena tagihan belum dilunasi.", 'rose', schoolAdmins: false);
        }
    }

    /**
     * Pastikan ada satu tagihan perpanjangan terbuka untuk periode berbayar yang akan habis.
     */
    public function ensureRenewalInvoice(School $school, SchoolSubscription $subscription): ?Invoice
    {
        if ($subscription->status === 'trial' || (float) $subscription->price_paid <= 0) {
            return null;
        }

        $hasOpen = Invoice::where('school_id', $school->id)
            ->whereIn('type', Invoice::PERIOD_TYPES)
            ->whereIn('status', ['unpaid', 'pending_verification'])
            ->exists();
        if ($hasOpen) {
            return null;
        }

        $plan = $subscription->plan;
        if (!$plan) {
            return null;
        }

        $months = $plan->periodMonths();
        $dueDate = $subscription->end_date->copy()->max(Carbon::today()->addDays(7));

        $invoice = $this->issueInvoice(
            $school,
            'renewal',
            "Perpanjangan Sewa {$plan->name} (+{$months} Bulan)",
            (float) $plan->price,
            $plan,
            $months,
            $subscription->student_quota,
            $subscription->storage_quota_mb,
            'Diterbitkan otomatis ' . self::RENEWAL_NOTICE_DAYS . ' hari sebelum masa sewa berakhir.',
            dueDate: $dueDate
        );

        AuditLogService::log('invoice_auto_renewal', 'billing',
            "Tagihan perpanjangan {$invoice->invoice_number} untuk {$school->name} diterbitkan otomatis.", $school);

        $this->notifySchool($school, 'invoice_issued', 'Tagihan perpanjangan terbit',
            "{$invoice->title} sebesar {$invoice->formatted_amount}, jatuh tempo {$invoice->due_date->format('d M Y')}.", 'amber');

        return $invoice;
    }

    // ------------------------------------------------------------------
    // Utilitas
    // ------------------------------------------------------------------

    public function notifySchool(
        School $school,
        string $event,
        string $title,
        string $message,
        string $color = 'amber',
        bool $schoolAdmins = true,
        bool $platformStaff = true
    ): void {
        if ($schoolAdmins) {
            $admins = User::where('school_id', $school->id)->where('role', 'admin-sekolah')->where('is_active', true)->get();
            Notification::send($admins, new SubscriptionLifecycleNotification($school, $event, $title, $message, '/admin-sekolah/billing', $color));
        }

        if ($platformStaff) {
            $staff = User::where('role', 'super-admin')->where('is_active', true)->get();
            Notification::send($staff, new SubscriptionLifecycleNotification(
                $school, $event, "{$school->name}: {$title}", $message, "/super-admin/schools/{$school->id}", $color
            ));
        }
    }

    public function nextInvoiceNumber(): string
    {
        do {
            $number = 'INV-' . date('Ym') . '-' . strtoupper(Str::random(5));
        } while (Invoice::where('invoice_number', $number)->exists());

        return $number;
    }
}
