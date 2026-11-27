@extends('errors.layout')

@section('title', 'Access Denied')
@section('code', '403')
@section('tone', 'access')

@section('message', 'You don’t have access to this page')

@section('description', 'Your account does not have the required permissions to access this page.')

@section('notice_text', 'Return to your dashboard or contact the system administrator if you believe this is a
    mistake.')
