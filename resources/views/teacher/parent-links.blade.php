@extends('layouts.app')

@section('page_title', 'Link Portal Monitoring Orang Tua Siswa')
@section('page_subtitle', 'Generate dan kelola tautan pemantauan PKL untuk orang tua siswa bimbingan atau rombel binaan Anda, serta ekspor file untuk dibagikan')

@section('content')
    @include('parent-links._view', [
        'roleType' => 'guru',
        'routePrefix' => 'guru.parent-links',
        'students' => $students,
        'majors' => $majors,
        'classes' => $classes,
        'stats' => $stats,
        'filters' => $filters,
        'teacher' => $teacher,
    ])
@endsection
