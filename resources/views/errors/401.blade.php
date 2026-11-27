@extends('errors.layout')

@section('title', 'Sign In Required')
@section('code', '401')
@section('tone', 'session')

@section('message', 'Please sign in to continue')

@section('description', 'You need to be signed in to access this page.')

@section('notice_text', 'Sign in to your account and try again.')

@section('primary_label', 'Sign In')
@section('primary_url', route('login'))
