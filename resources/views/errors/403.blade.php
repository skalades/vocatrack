@extends('errors.layout')

@section('title', '403 Akses Dilarang')
@section('code', '403')
@section('dot_class', 'bg-rose-500')
@section('badge_class', 'text-rose-800')
@section('badge_text', 'Access Denied')
@section('heading', 'Akses Dibatasi atau Ditolak')
@section('message', $exception->getMessage() ?: 'Anda tidak memiliki hak akses atau wewenang untuk melihat dan mengelola data pada sumber daya ini.')
