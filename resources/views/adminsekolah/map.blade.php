@extends('layouts.app')

@section('page_title', 'Peta Sebaran Mitra DU/DI & Siswa')
@section('page_subtitle', 'Monitoring geografis sebaran lokasi magang DU/DI dan status kehadiran harian siswa PKL')

@section('content')
    @include('map._map_view', [
        'roleType' => 'admin',
        'routePrefix' => 'adminsekolah.map',
        'mapData' => $mapData,
        'majors' => $majors,
        'classes' => $classes,
        'filters' => $filters,
    ])
@endsection
