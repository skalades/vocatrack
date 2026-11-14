<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Faktur Tagihan #{{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 30px 40px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.4;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .logo-box {
            font-size: 22px;
            font-weight: 900;
            color: #4338ca;
            letter-spacing: -0.5px;
        }
        .tagline {
            font-size: 9px;
            font-weight: bold;
            color: #6366f1;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .inv-meta {
            font-size: 10px;
            color: #64748b;
            margin-top: 4px;
        }
        .inv-meta strong {
            color: #0f172a;
            font-family: monospace;
            font-size: 11px;
        }
        .divider {
            height: 2px;
            background: #e2e8f0;
            margin: 15px 0 20px 0;
        }
        .info-table {
            margin-bottom: 25px;
        }
        .info-table td {
            vertical-align: top;
            width: 50%;
        }
        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 14px;
        }
        .info-card h4 {
            margin: 0 0 6px 0;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #475569;
        }
        .info-card p {
            margin: 2px 0;
            font-size: 10.5px;
        }
        .info-card .name {
            font-weight: bold;
            color: #0f172a;
            font-size: 12px;
        }
        .items-table {
            margin-bottom: 20px;
        }
        .items-table th {
            background: #0f172a;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
        }
        .items-table th.right {
            text-align: right;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10.5px;
        }
        .items-table td.right {
            text-align: right;
            font-family: monospace;
            font-weight: bold;
        }
        .total-box {
            width: 45%;
            margin-left: auto;
            margin-bottom: 25px;
        }
        .total-box table td {
            padding: 5px 10px;
        }
        .total-box .grand-total {
            background: #f1f5f9;
            font-size: 13px;
            font-weight: 900;
            color: #4338ca;
            border-top: 2px solid #cbd5e1;
        }
        .stamp-box {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stamp-paid {
            background: #ecfdf5;
            color: #047857;
            border: 2px solid #059669;
        }
        .stamp-unpaid {
            background: #fffbeb;
            color: #b45309;
            border: 2px solid #d97706;
        }
        .stamp-cancelled {
            background: #fef2f2;
            color: #b91c1c;
            border: 2px solid #dc2626;
        }
        .payment-notes {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 25px;
            font-size: 10px;
            color: #475569;
        }
        .payment-notes h5 {
            margin: 0 0 4px 0;
            font-size: 10px;
            font-weight: 800;
            color: #0f172a;
        }
        .footer-text {
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <!-- Header Table -->
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <div class="logo-box">{{ $platformName }}</div>
                <div class="tagline">{{ $platformTagline }}</div>
                <p style="margin-top: 6px; color: #64748b; font-size: 10px; max-width: 280px;">
                    Platform PKL SMK Terpadu Berbasis Multi-Tenancy Cloud.<br>
                    Layanan Langganan & Lisensi Satuan Pendidikan.
                </p>
            </td>
            <td style="width: 45%;" class="invoice-title">
                <h1>FAKTUR TAGIHAN</h1>
                <div class="inv-meta">
                    Nomor: <strong>{{ $invoice->invoice_number }}</strong><br>
                    Tanggal Terbit: {{ $invoice->created_at->format('d/m/Y') }}<br>
                    Jatuh Tempo: <strong>{{ $invoice->due_date->format('d/m/Y') }}</strong>
                </div>
                <div style="margin-top: 8px;">
                    @if($invoice->status === 'paid')
                        <span class="stamp-box stamp-paid">&#10003; LUNAS</span>
                    @elseif($invoice->status === 'cancelled')
                        <span class="stamp-box stamp-cancelled">&#10005; DIBATALKAN</span>
                    @else
                        <span class="stamp-box stamp-unpaid">MENUNGGU PEMBAYARAN</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Info Table: Pihak Penagih & Tertagih -->
    <table class="info-table">
        <tr>
            <td style="padding-right: 10px;">
                <div class="info-card">
                    <h4>Tertagih Kepada (Tenant Sekolah):</h4>
                    <p class="name">{{ $invoice->school->name ?? 'Sekolah Mitra' }}</p>
                    <p>NPSN: <strong>{{ $invoice->school->npsn ?? '-' }}</strong></p>
                    <p>Alamat: {{ $invoice->school->address ?? '-' }}</p>
                    <p>Telepon: {{ $invoice->school->phone ?? '-' }} | Email: {{ $invoice->school->email ?? '-' }}</p>
                </div>
            </td>
            <td style="padding-left: 10px;">
                <div class="info-card">
                    <h4>Penerbit & Rekening Pembayaran:</h4>
                    <p class="name">{{ $platformName }} Billing Operations</p>
                    <p>Metode Pembayaran: <strong>{{ $invoice->payment_method ?? 'Transfer Bank / APBD BOS' }}</strong></p>
                    @if($invoice->status === 'paid' && $invoice->paid_at)
                        <p style="color: #059669; font-weight: bold;">
                            Diverifikasi Lunas Pada: {{ $invoice->paid_at->format('d M Y, H:i') }} WIB
                        </p>
                    @else
                        <p style="color: #d97706;">
                            Harap selesaikan pembayaran sebelum tanggal jatuh tempo.
                        </p>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Rincian Item Tagihan -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;">Deskripsi Layanan / Paket Lisensi</th>
                <th style="width: 25%;">Spesifikasi & Kuota</th>
                <th style="width: 25%;" class="right">Nominal (IDR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong style="color: #0f172a; font-size: 11px;">{{ $invoice->title }}</strong>
                    <div style="font-size: 9.5px; color: #64748b; margin-top: 2px;">
                        Langganan Cloud Multi-Tenancy PKL &bull; Tenant: {{ $invoice->school->name }}
                    </div>
                </td>
                <td>
                    @if($invoice->subscription)
                        <div>Paket: <strong>{{ $invoice->subscription->plan_name }}</strong></div>
                        <div style="font-size: 9.5px; color: #64748b;">
                            Kuota: {{ $invoice->subscription->student_quota }} Siswa | Storage: {{ round($invoice->subscription->storage_quota_mb / 1024, 1) }} GB
                        </div>
                    @else
                        <span>Layanan Lisensi Platform</span>
                    @endif
                </td>
                <td class="right">
                    {{ $invoice->formatted_amount }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Total Perhitungan -->
    <div class="total-box">
        <table>
            <tr>
                <td style="color: #64748b;">Subtotal:</td>
                <td style="text-align: right; font-family: monospace;">{{ $invoice->formatted_amount }}</td>
            </tr>
            <tr>
                <td style="color: #64748b;">Pajak / PPN:</td>
                <td style="text-align: right; font-family: monospace;">Rp 0</td>
            </tr>
            <tr class="grand-total">
                <td>Total Tagihan:</td>
                <td style="text-align: right; font-family: monospace;">{{ $invoice->formatted_amount }}</td>
            </tr>
        </table>
    </div>

    <!-- Catatan Tambahan -->
    @if($invoice->notes)
        <div class="payment-notes">
            <h5>Catatan Khusus / Instruksi Pembayaran:</h5>
            <p style="margin: 0;">{{ $invoice->notes }}</p>
        </div>
    @endif

    <!-- Footer Otentikasi -->
    <div class="footer-text">
        Faktur elektronik ini diterbitkan secara otomatis oleh sistem SaaS {{ $platformName }}.<br>
        Dokumen ini sah sebagai bukti penagihan / pembayaran resmi tanpa memerlukan tanda tangan basah.
    </div>

</body>
</html>
