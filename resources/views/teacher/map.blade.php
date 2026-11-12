@extends('layouts.app')

@section('page_title', 'Peta Sebaran Siswa Bimbingan PKL')
@section('page_subtitle', 'Visualisasi spasial persebaran lokasi magang DU/DI siswa binaan dan status kehadiran hari ini')

@section('content')
    @include('map._map_view', [
        'roleType' => 'guru',
        'routePrefix' => 'guru.map',
        'mapData' => $mapData,
        'majors' => $majors,
        'classes' => $classes,
        'filters' => $filters,
        'teacher' => $teacher,
    ])
@endsection
