@extends('errors.layout')

@section('title', __('Server Error'))
@section('code', '500')
@section('message', __('Internal Server Error'))
@section('description', __("Whoops! Something went unexpectedly wrong on our servers. Our technical team has been notified. Please try again later."))
