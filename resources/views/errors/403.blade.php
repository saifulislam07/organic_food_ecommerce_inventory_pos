@extends('errors.layout')

@section('title', app()->getLocale() == 'bn' ? 'প্রবেশাধিকার নেই' : 'Forbidden')
@section('code', '403')
@section('message', app()->getLocale() == 'bn' ? 'প্রবেশাধিকার নেই' : 'Access Denied')
@section('description', app()->getLocale() == 'bn'
    ? 'এই পাতায় প্রবেশের অনুমতি আপনার নেই। অনুগ্রহ করে অনুমোদিত অ্যাকাউন্ট দিয়ে লগইন করুন অথবা হোম পেজে ফিরে যান।'
    : "You don't have the required permissions to access this page. Please log in with an authorized account or return to the homepage.")
