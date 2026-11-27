@extends('errors.layout')

@section('title', 'Something Went Wrong')
@section('code', '500')
@section('tone', 'system')

@section('message', 'Something went wrong on our end')

@section('description',
    'Your request wasn’t completed due to an unexpected system issue.')

@section('notice_text',
    'Please wait a moment and try again.')