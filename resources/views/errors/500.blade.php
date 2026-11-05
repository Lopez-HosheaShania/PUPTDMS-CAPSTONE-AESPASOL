@extends('errors.layout')

@section('title', 'Something Went Wrong')
@section('code', '500')
@section('tone', 'system')
@section('label', 'Request not completed')
@section('message', 'We couldn’t complete your request.')
@section('description', 'An unexpected issue stopped the system from completing the action.')
@section('notice_text', 'Try again after a moment. If the problem continues, contact the system administrator.')

@section('notice_icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 9v4m0 4h.01M10.29 3.86l-7.5 13A2 2 0 0 0 4.5 20h15a2 2 0 0 0 1.71-3.14l-7.5-13a2 2 0 0 0-3.42 0z" />
    </svg>
@endsection
