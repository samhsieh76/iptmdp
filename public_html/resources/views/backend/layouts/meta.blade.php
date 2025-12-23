<title>@yield('seo_title', config('app.name'))</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="@yield('og_description', '智慧化公廁管理戰情平台')">
<link rel="canonical" href="{{ Request::url() }}" />
<meta property="og:title" content="@yield('seo_title', '智慧化公廁管理戰情平台')">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ Request::url() }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image" content="@yield('og_img', '智慧化公廁管理戰情平台')">
<meta property="og:description" content="@yield('og_description', '智慧化公廁管理戰情平台')">

<link rel="apple-touch-icon" sizes="180x180" href="{{asset('favicons/apple-icon-180x180.png')}}">
<link rel="icon" type="image/png" sizes="32x32" href="{{asset('favicons/favicon-32x32.png')}}">
<link rel="icon" type="image/png" sizes="16x16" href="{{asset('favicons/favicon-16x16.png')}}">