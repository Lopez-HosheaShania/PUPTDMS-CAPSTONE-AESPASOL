@extends('errors.layout')

@section('title', 'Page Not Found')
@section('code', '404')
@section('tone', 'missing')

@section('label', 'Page not found')
@section('message', 'We couldn’t find that page.')

@section('description', 'The page may have been moved, renamed, removed, or the link may be incorrect.')

@section('notice_text', 'Check the link, go back to the previous page, or return home to continue.')

@section('notice_icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">

        <circle cx="11" cy="11" r="6"></circle>

        <path stroke-linecap="round" stroke-linejoin="round" d="M20 20l-3.5-3.5">
        </path>
    </svg>
@endsection
