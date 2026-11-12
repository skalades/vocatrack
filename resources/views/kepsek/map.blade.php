@extends('layouts.app')

@section('page_title', 'Peta Sebaran PKL & Monitoring Wilayah')
@section('page_subtitle', 'Dashboard eksekutif pemantauan geografis sebaran seluruh siswa PKL dan mitra industri sekolah')

@section('content')
    @include('map._map_view', [
        'roleType' => 'kepsek',
        'routePrefix' => 'kepsek.map',
        'mapData' => $mapData,
        'majors' => $majors,
        'classes' => $classes,
        'filters' => $filters,
    ])
@endsection
