<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Web Crawler Showcase: Uncover the Magic of Data Crawling | By Ezequiel Pereira</title>
    <meta name="description" content="Embark on a journey through the digital landscape with our Web Crawler Showcase. Witness the fascinating capabilities of web crawling as we traverse the internet's depths, indexing information and unraveling the mysteries of online data. Explore, learn, and be inspired by the possibilities of web crawling technology.">
    <meta http-equiv="Cache-Control" content="max-age=290304000, public">
    <link rel="apple-touch-icon" sizes="180x180" href="/img/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/img/icons/favicon-16x16.png">
    <link rel="manifest" href="/img/icons/site.webmanifest">
    <link rel="mask-icon" href="/img/icons/safari-pinned-tab.svg" color="#5bbad5">
    <link rel="shortcut icon" href="/img/icons/favicon.ico">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-config" content="/img/icons/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">
    <link rel="canonical" href="https://web-crawler.ezequielhpp.net"/>
    <link rel="preconnect" href="https://www.googletagmanager.com/" crossorigin>
    <meta property="og:title" content="Web Crawler Showcase: Uncover the Magic of Data Crawling | Web Crawler by Ezequiel Pereira">
    <meta property="og:description" content="Embark on a journey through the digital landscape with our Web Crawler Showcase. Witness the fascinating capabilities of web crawling as we traverse the internet's depths, indexing information and unraveling the mysteries of online data. Explore, learn, and be inspired by the possibilities of web crawling technology.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://web-crawler.ezequielhpp.net">
    <meta property="og:image" content="https://web-crawler.ezequielhpp.net/img/og_tag.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Web Crawler by Ezequiel Pereira">
    <meta property="og:site_name" content="Web Crawler by Ezequiel Pereira">

    @yield('styles')
    @yield('meta')
    @yield('schema')
    @yield('preConnect')
</head>
<body>
<header class="main">
    @yield('header')
</header>
<main>
    @yield('main')
</main>
<footer>
    @include('partials.footer')
</footer>
@yield('scripts')
</body>
</html>
