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

<body oncontextmenu ="return false">
    {{-- <div id="app" class="contact-container">
            <div class="row contact-topside">
                <img class="logo" src="{{ asset('images/login_logo.svg') }}">
            </div>
            <div class="row contact-box">
                <div class="col-12 contact-text">
                    <span>@{{ $t('contact') }}</span>
                </div>
                <div class="col-12 contact-info">
                    <span v-html="$t('contact_info')"></span>
                </div>
                <div class="col-12 contact-qrcode p-1">
                    <img src="{{ asset('images/contact_qrcode.jpg') }}">
                </div>
                <div class="col-12 contact-btn">
                    <a href="{{ route('welcome') }}" class="btn btn-back">@{{ $t('back') }}</a>
                </div>
            </div>
        </div> --}}
    <div id="app" class="container-fluid h-70" v-cloak>
        <div class="row h-100">
            <div class="contact-container col-10 mx-auto">
                <div class="row contact-topside">
                    <img class="logo" src="{{ asset('images/login_logo.svg') }}">
                </div>
                <div class="body">
                    <div class="contact-box">
                        <div class="contact-text">
                            <span>@{{ $t('contact') }}</span>
                        </div>
                        <div class="contact-info">
                            <span v-html="$t('contact_info')"></span>
                        </div>
                        <div class="contact-qrcode p-1">
                            <img src="{{ asset('images/contact_qrcode.jpg') }}">
                        </div>
                        <div class="contact-btn">
                            <a href="{{ route('welcome') }}" class="btn btn-back">@{{ $t('back') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer id="footer">
        <img class="logo" src="{{ asset('images/welcome_epa_gov_logo.svg') }}">
        <span class="copy_text">
            {{ trans('messages.copyright', ['year' => date('Y')]) }}
        </span>
    </footer>
    <script src="{{ asset('assets/js/login.js') }}"></script>
</body>

</html>
