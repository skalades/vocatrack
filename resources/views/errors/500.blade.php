@extends('errors.layout')

@section('title', '500 Kesalahan Server')
@section('code', '500')
@section('dot_class', 'bg-rose-500 animate-pulse')
@section('badge_class', 'text-rose-800')
@section('badge_text', 'Internal Server Error')
@section('heading', 'Terjadi Gangguan pada Server')
@section('message', 'Sistem kami sedang mengalami kendala teknis tak terduga. Tim pengembang telah mencatat insiden ini untuk segera ditangani.')
