@extends('layouts.app')

@section('page_title', 'Link Portal Monitoring Orang Tua Rombel')
@section('page_subtitle', 'Kelola, generate, dan ekspor tautan pemantauan PKL untuk orang tua siswa rombel Anda')

@section('content')
    @include('parent-links._view', [
        'roleType' => 'walikelas',
        'routePrefix' => 'walikelas.parent-links',
        'students' => $students,
        'majors' => $majors,
        'classes' => $classes,
        'stats' => $stats,
        'filters' => $filters,
    ])
@endsection
