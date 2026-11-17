@extends('errors.layout')

@section('title', '404 Halaman Tidak Ditemukan')
@section('code', '404')
@section('dot_class', 'bg-[#0B2B25]/60')
@section('badge_class', 'text-[#0B2B25]')
@section('badge_text', 'Not Found')
@section('heading', 'Halaman Tidak Ditemukan')
@section('message', 'Tautan yang Anda tuju mungkin sudah dipindahkan, dihapus, atau tidak pernah tersedia di sistem ' . ($systemName ?? 'VokaTrack') . '.')
