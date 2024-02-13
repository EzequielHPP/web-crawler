@extends('template')

@section('styles')
    <link rel="stylesheet" href="{{mix('css/homepage.css')}}">
@endsection

@section('meta')
    <meta name="description" content="Ezequiel Pereira's Web Crawler">
    <meta name="keywords" content="web crawler, ezequiel pereira">
    <meta name="author" content="Ezequiel Pereira">
@endsection

@section('schema')
    <script type="application/ld+json">
        {
          "@context": "http://schema.org",
          "@type": "WebSite",
          "name": "Web Crawler Showcase",
          "description": "Embark on a journey through the digital landscape with our Web Crawler Showcase. Witness the fascinating capabilities of web crawling as we traverse the internet's depths, indexing information and unraveling the mysteries of online data. Explore, learn, and be inspired by the possibilities of web crawling technology.",
          "url": "https://web-crawler.ezequielhpp.net"
        }
    </script>
@endsection

@section('preConnect')
    <link rel="preconnect" href="https://web-crawler.ezequielhpp.net">
    <link rel="prefetch" href="/img/logo.png">
    <link rel="prefetch" href="/img/logo.webp">
    <link rel="prefetch" href="/img/icons/apple-touch-icon.png">
    <link rel="prefetch" href="/img/icons/favicon-32x32.png">
    <link rel="prefetch" href="/img/icons/favicon-16x16.png">
    <link rel="prefetch" href="/img/icons/site.webmanifest">
    <link rel="prefetch" href="/fonts/pxiByp8kv8JHgFVrLCz7Z11lFc-K.woff2">
    <link rel="prefetch" href="/fonts/pxiByp8kv8JHgFVrLCz7Z1JlFc-K.woff2">
    <link rel="prefetch" href="/fonts/pxiByp8kv8JHgFVrLCz7Z1xlFQ.woff2">
    <link rel="prefetch" href="/fonts/pxiByp8kv8JHgFVrLDz8Z11lFc-K.woff2">
    <link rel="prefetch" href="/fonts/pxiByp8kv8JHgFVrLDz8Z1JlFc-K.woff2">
    <link rel="prefetch" href="/fonts/pxiByp8kv8JHgFVrLDz8Z1xlFQ.woff2">
    <link rel="prefetch" href="/fonts/pxiByp8kv8JHgFVrLEj6Z11lFc-K.woff2">
    <link rel="prefetch" href="/fonts/pxiByp8kv8JHgFVrLEj6Z1JlFc-K.woff2">
    <link rel="prefetch" href="/fonts/pxiByp8kv8JHgFVrLEj6Z1xlFQ.woff2">
    <link rel="prefetch" href="/fonts/pxiEyp8kv8JHgFVrJJbecmNE.woff2">
    <link rel="prefetch" href="/fonts/pxiEyp8kv8JHgFVrJJfecg.woff2">
    <link rel="prefetch" href="/fonts/pxiEyp8kv8JHgFVrJJnecmNE.woff2">
@endsection

@section('header')
    <h1>Web Crawler</h1>
    <picture class="logo">
        <source srcset="/img/logo.webp" type="image/webp">
        <img src="/img/logo.png" alt="Web Crawler by Ezequiel Pereira" width="300" height="135">
    </picture>
    <h2>By <a href="https://ezequielhpp.net" rel="opener" target="_blank">Ezequiel Pereira</a></h2>
@endsection

@section('main')
    <div id="status">
        <div id="queued" class="status"><label>Queued:</label><span>0</span></div>
        <div id="in-progress" class="status"><label>In Progress:</label><span>0</span></div>
    </div>
    <form id="crawler" action="{{route('api.scan')}}" method="post">
        @csrf
        <input type="hidden" name="key" value="" id="crawl-key">
        <input id="url" type="text" name="url" placeholder="URL To Crawl">
        <button type="submit">Go!</button>
    </form>
    <div id="running">
        <button id="stop" data-url="{{route('api.scan.stop',['key'=>'key'])}}">Stop Crawl</button>
    </div>
    <div id="results" data-status="{{route('api.scan.status')}}"
         data-results="{{route('api.scan.key',['key'=>'key'])}}">
        <div class="holder"></div>
    </div>
@endsection

@section('scripts')
    <script src="{{mix('js/homepage.js')}}"></script>
@endsection
