@extends('errors.layout')

@section('title', 'Sign In Required')
@section('code', '401')
@section('tone', 'session')

@section('label', 'Sign in required')
@section('message', 'Please sign in to continue.')

@section('description', 'Your session is not currently signed in, so this page cannot be opened.')

@section('notice_text', 'Return to the sign-in page, then try opening this section again.')

@section('primary_label', 'Sign In')
@section('primary_url', route('login'))

@section('notice_icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">

        <path stroke-linecap="round" stroke-linejoin="round"
            d="M16.5 10.5V7.75a4.25 4.25 0 1 0-8.5 0v2.75m-1.25 0h10.5A1.75 1.75 0 0 1 19 12.25v6A1.75 1.75 0 0 1 17.25 20h-10.5A1.75 1.75 0 0 1 5 18.25v-6A1.75 1.75 0 0 1 6.75 10.5Z">
        </path>
    </svg>
@endsection
