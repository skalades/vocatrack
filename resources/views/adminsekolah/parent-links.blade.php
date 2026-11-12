@extends('layouts.app')

@section('page_title', 'Kelola Link Portal Monitoring Orang Tua')
@section('page_subtitle', 'Generate tautan pemantauan PKL untuk orang tua/wali siswa secara massal dan ekspor data per jurusan & rombel untuk dibagikan')

@section('content')
    @include('parent-links._view', [
        'roleType' => 'admin',
        'routePrefix' => 'adminsekolah.parent-links',
        'students' => $students,
        'majors' => $majors,
        'classes' => $classes,
        'stats' => $stats,
        'filters' => $filters,
    ])
@endsection
