<!DOCTYPE html>
<html lang="zh-Hant-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>{{ config('app.name', 'Laravel') }}</title>

    @include('backend.layouts.meta')
    @include('backend.layouts.css')
    {{-- =============== 補充該頁才有的css =============== --}}
    @yield('add-css')
</head>

<body class="{{ isset($sidebar)?$sidebar:''}}" oncontextmenu ="return false">
    <div class="preloader"><span></span></div>{{--  /.preloader --}}
    <div id="app" class="page-wrapper">
        @include('backend.layouts.header')
        @yield('content')
        @include('backend.layouts.footer')
        <v-self-password-modal ref="settingSelfPasswordModal"></v-self-password-modal>
        @yield('modal')
    </div>
    @include('backend.layouts.script')
    @yield('add-js')
</body>

</html>
