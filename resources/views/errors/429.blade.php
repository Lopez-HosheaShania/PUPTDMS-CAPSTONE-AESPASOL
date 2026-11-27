@extends('errors.layout')

@section('title', 'Too Many Requests')
@section('code', '429')
@section('tone', 'access')

@section('message', 'You’re sending too many requests')

@section('description', 'The system received too many requests within a short period.')

@section('notice_text', 'Please wait a moment before trying again. Avoid repeatedly clicking or refreshing the page.')
