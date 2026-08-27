@extends('errors.layout')

@section('title', app()->getLocale() == 'bn' ? 'পাতা পাওয়া যায়নি' : 'Page Not Found')
@section('code', '404')
@section('image', asset('images/errors/404.png'))
@section('message', app()->getLocale() == 'bn' ? 'আপনার গন্তব্য খুঁজে পাওয়া যায়নি!' : "We couldn't find that page")
@section('description', app()->getLocale() == 'bn'
    ? 'আমরা দুঃখিত, আপনি যে পাতাটি খুঁজছেন সেটি বর্তমানে উপলব্ধ নেই। সম্ভবত এটি স্থানান্তরিত হয়েছে অথবা মুছে ফেলা হয়েছে।'
    : 'Sorry, the page you are looking for is not available. It may have moved or been removed.')
