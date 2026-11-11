@extends('errors.layout')

@section('title', '403 Akses Dilarang')
@section('code', '403')
@section('badge_class', 'text-rose-400')
@section('badge_text', 'Access Denied')
@section('heading', 'Akses Dibatasi atau Ditolak')
@section('message', $exception->getMessage() ?: 'Anda tidak memiliki hak akses atau wewenang untuk melihat dan mengelola data pada sumber daya ini.')
