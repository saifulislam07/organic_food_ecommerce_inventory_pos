@extends('errors.layout')

@section('title', __('HTTP Version Not Supported'))
@section('code', '505')
@section('message', __('Protocol Not Supported'))
@section('description', __("The HTTP protocol version used in the request is not supported by our server. Please upgrade your browser or client."))
