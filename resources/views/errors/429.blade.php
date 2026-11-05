@extends('errors.layout')

@section('title', 'Please Wait')
@section('code', '429')
@section('tone', 'access')

@section('label', 'Please wait')

@section('message', 'Please wait a moment before trying again.')

@section('description', 'The system received several repeated requests in a short time.')

@section('notice_text', 'Wait briefly, then retry the action once. Avoid repeatedly clicking or refreshing.')

@section('notice_icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01"></path>
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M10.29 3.86l-7.5 13A2 2 0 0 0 4.5 20h15a2 2 0 0 0 1.71-3.14l-7.5-13a2 2 0 0 0-3.42 0z" />
    </svg>
@endsection
