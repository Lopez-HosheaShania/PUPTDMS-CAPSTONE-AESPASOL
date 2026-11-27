@extends('errors.layout')

@section('title', 'Service Unavailable')
@section('code', '503')
@section('tone', 'system')

@section('message', 'The system is temporarily unavailable')

@section('description', 'The clinic system may be undergoing maintenance or experiencing a temporary service
    interruption.')

@section('notice_text', 'Please try again in a few minutes.')

@section('primary_label', 'Refresh Page')
@section('primary_url', request()->fullUrl())
