@php
    $bn = app()->getLocale() == 'bn';
@endphp

<section
    data-vue="ConfirmDeleteAccount"
    data-props="{{ json_encode([
        'action' => route('profile.destroy'),
        'open' => $errors->userDeletion->isNotEmpty(),
        'error' => $errors->userDeletion->first('password') ?: null,
        'labels' => [
            'warning' => $bn
                ? 'আপনার অ্যাকাউন্ট মুছে ফেলা হলে, এর সমস্ত তথ্য স্থায়ীভাবে মুছে যাবে। অ্যাকাউন্টটি মুছে ফেলার আগে, দয়া করে আপনার প্রয়োজনীয় কোনো তথ্য থাকলে ডাউনলোড করে নিন।'
                : 'Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.',
            'trigger' => $bn ? 'অ্যাকাউন্ট মুছে ফেলুন' : 'Delete Account',
            'title' => $bn
                ? 'আপনি কি নিশ্চিত যে আপনি আপনার অ্যাকাউন্টটি মুছে ফেলতে চান?'
                : 'Are you sure you want to delete your account?',
            'body' => $bn
                ? 'একবার আপনার অ্যাকাউন্ট মুছে ফেলা হলে, এর সমস্ত ডেটা স্থায়ীভাবে মুছে যাবে। আপনার অ্যাকাউন্ট স্থায়ীভাবে মুছে ফেলতে চাইলে অনুগ্রহ করে আপনার পাসওয়ার্ড লিখুন।'
                : 'Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.',
            'password' => $bn ? 'পাসওয়ার্ড' : 'Password',
            'passwordPlaceholder' => $bn ? 'পাসওয়ার্ড লিখুন' : 'Password',
            'cancel' => $bn ? 'বাতিল' : 'Cancel',
            'confirm' => $bn ? 'মুছে ফেলুন' : 'Delete Account',
        ],
    ], JSON_UNESCAPED_UNICODE) }}"
></section>
