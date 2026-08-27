@extends('errors.layout')

@section('title', app()->getLocale() == 'bn' ? 'HTTP সংস্করণ সমর্থিত নয়' : 'HTTP Version Not Supported')
@section('code', '505')
@section('message', app()->getLocale() == 'bn' ? 'প্রোটোকল সমর্থিত নয়' : 'Protocol Not Supported')
@section('description', app()->getLocale() == 'bn'
    ? 'আপনার রিকোয়েস্টে ব্যবহৃত HTTP প্রোটোকল সংস্করণটি আমাদের সার্ভার সমর্থন করে না। অনুগ্রহ করে ব্রাউজার আপডেট করুন।'
    : 'The HTTP protocol version used in the request is not supported by our server. Please upgrade your browser or client.')
