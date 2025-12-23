<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('backend.layouts.meta')
    @section('seo_title')
    {{ config('app.name', 'Laravel') }}
    @endsection

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/mdbootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/login/style.css') }}">
</head>

<body oncontextmenu ="return false" class="welcome-page">
    <div id="app" class="welcome-box h-70" v-cloak>
        <flash-messages-redirect></flash-messages-redirect>
        <div class="row h-100">
            <div class="col-auto mx-auto">
                <img width="535" height="auto" class="logo" src="{{ asset('images/welcome_logo.svg') }}" alt="welcome_logo">
            </div>
            <div class="btn-layout col-12">
                <div class="col-auto">
                    <a href="{{ route('home') }}" class="btn btn-welcome">@{{ $t('welcome_start') }}</a>
                </div>
                <div class="col-auto">
                    <a href="{{ route('contact') }}" class="btn btn-welcome">@{{ $t('contact') }}</a>
                </div>
            </div>
        </div>
    </div>
    <footer id="footer">
        <img class="logo" height="70" width="auto" src="{{ asset('images/welcome_epa_gov_logo.svg') }}" alt="logo">
        <span class="copy_text">
            {{ trans('messages.copyright', ['year' => date('Y')]) }}
        </span>
    </footer>
    <script src="{{ asset('assets/js/login.js') . '?v1.2' }}"></script>
</body>

</html>
