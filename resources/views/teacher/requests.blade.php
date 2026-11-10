@extends('layouts.app')

@section('page_title', 'Verifikasi Permohonan Izin & Sakit')
@section('page_subtitle', 'Tinjau dan setujui permohonan presensi khusus siswa bimbingan')

@section('content')
<div class="space-y-6">

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-800">Daftar Pengajuan Masuk</h3>
            <p class="text-xs text-slate-500">Guru pembimbing bertindak sebagai verifikator utama permohonan Izin dan Sakit</p>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($requests as $req)
                <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-slate-50/50 transition-colors">
                    <div class="space-y-1.5 flex-1">
                        <div class="flex items-center space-x-2">
                            <span class="font-bold text-slate-900 text-xs">{{ $req->studentPkl->student->user->name }}</span>
                            <span class="text-[11px] text-slate-400">({{ $req->studentPkl->company->name }})</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $req->type === 'izin' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-purple-50 text-purple-700 border border-purple-200' }}">
                                {{ strtoupper($req->type) }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-700 font-medium">
                            Periode: <strong>{{ \Carbon\Carbon::parse($req->start_date)->format('d M Y') }}</strong> s.d. <strong>{{ \Carbon\Carbon::parse($req->end_date)->format('d M Y') }}</strong>
                        </p>
                        <p class="text-xs text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-100 italic">
                            "{{ $req->reason }}"
                        </p>
                        @if($req->attachment_path)
                            <a href="{{ asset('storage/' . $req->attachment_path) }}" target="_blank" class="inline-flex items-center text-xs font-bold text-indigo-600 hover:text-indigo-800">
                                <span>📎 Lihat Lampiran Surat / Bukti</span>
                            </a>
                        @endif
                    </div>

                    <!-- Action form -->
                    <div class="md:w-72">
                        @if($req->status === 'pending')
                            <form action="{{ route('guru.requests.process', $req->id) }}" method="POST" class="space-y-2">
                                @csrf
                                <input type="text" name="reviewer_notes" placeholder="Catatan persetujuan / alasan penolakan..." class="w-full text-xs p-2 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="flex space-x-2">
                                    <button type="submit" name="action" value="reject" onclick="return confirm('Tolak permohonan ini?')" class="flex-1 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs border border-rose-200">
                                        Tolak
                                    </button>
                                    <button type="submit" name="action" value="approve" class="flex-1 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-200">
                                        Setujui
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="text-right">
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $req->status === 'approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    {{ strtoupper($req->status) }}
                                </span>
                                @if($req->reviewer_notes)
                                    <p class="text-[11px] text-slate-500 mt-1 italic">"{{ $req->reviewer_notes }}"</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-slate-400 text-xs">
                    Tidak ada permohonan izin atau sakit yang diajukan.
                </div>
            @endforelse
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $requests->links() }}
        </div>
    </div>

</div>
@endsection
