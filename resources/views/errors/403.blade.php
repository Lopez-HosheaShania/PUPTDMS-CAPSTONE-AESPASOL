@extends('errors.layout')

@section('title', 'Access Not Available')
@section('code', '403')
@section('tone', 'access')

@section('label', 'Access not available')

@section('message', 'This page isn’t available to your account.')

@section('description', 'Your current account role does not include access to this section of the clinic system.')

@section('notice_text', 'Return to a page you can access. If you believe this is a mistake, contact the system administrator.')

@section('notice_icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">

        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3 5 6.5V12c0 4.5 3 7.1 7 9 4-1.9 7-4.5 7-9V6.5L12 3Z">
        </path>

        <path stroke-linecap="round" d="m9 9 6 6M15 9l-6 6">
        </path>
    </svg>
@endsection
