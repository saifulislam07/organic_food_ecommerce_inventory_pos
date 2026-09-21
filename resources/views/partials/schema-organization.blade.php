{{--
    Who the shop is, in the form Google reads.

    Site-wide, so it sits in the frontend layout rather than in any one page.
    It is what lets a search for the brand name show the logo and the proper
    name instead of a guess made from the page title.

    The payload is built as an array and encoded, never written out by hand:
    JSON_HEX_TAG turns any "<" in an admin-typed shop name into <, so a
    stray </script> in the settings cannot break out of this block.
--}}
@php
    $siteTitle = \App\Models\Setting::get('site_title', 'BaburhashiBD');
    $uploadedLogo = \App\Models\Setting::value('logo');

    $organization = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'OnlineStore',
        'name' => $siteTitle,
        'url' => url('/'),
        'logo' => $uploadedLogo
            ? \App\Support\ImageStore::url($uploadedLogo)
            : asset('assets/img/logo.png'),
        'description' => \App\Support\SeoSettings::get('seo_meta_description') ?: null,
        'image' => \App\Support\SeoSettings::ogImageUrl(),
    ]);
@endphp
<script type="application/ld+json">
{!! json_encode($organization, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_PRETTY_PRINT) !!}
</script>
