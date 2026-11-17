@extends('errors.layout')

@section('title', '503 Pemeliharaan Sistem')
@section('code', '503')
@section('dot_class', 'bg-amber-500 animate-pulse')
@section('badge_class', 'text-amber-800')
@section('badge_text', 'Service Unavailable / Maintenance')
@section('heading', 'Sistem Sedang Dalam Pemeliharaan')
@section('message', 'Platform ' . ($systemName ?? 'VokaTrack') . ' sedang melakukan pembaruan rutin untuk meningkatkan performa dan keandalan sistem. Layanan akan kembali normal dalam beberapa saat.')
