@extends('errors.layout')

@section('title', __('Forbidden'))
@section('code', '403')
@section('message', __('Access Denied'))
@section('description', __("You don't have the required permissions to access this page. Please log in with an authorized account or return to the homepage."))
