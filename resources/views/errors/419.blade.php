@extends('errors.layout')

@section('title', 'Page Expired')
@section('code', '419')
@section('tone', 'session')

@section('message', 'Your session has expired')

@section('description', 'Your previous request could not be completed for security reasons.')

@section('notice_text', 'Sign in again or reopen the page to continue.')

@section('primary_label', 'Sign In Again')
@section('primary_url', route('login'))
