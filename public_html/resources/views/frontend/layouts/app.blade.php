<!DOCTYPE html>
<html lang="zh-Hant-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>{{ config('app.name', 'Laravel') }}</title>

    @include('frontend.layouts.meta')
    @include('frontend.layouts.css')
    {{-- =============== 補充該頁才有的css =============== --}}
    @yield('add-css')

    <style>


    </style>
</head>

<body oncontextmenu ="return false">
    <div class="preloader"><span></span></div>{{--  /.preloader --}}
    <div id="app" class="page-wrapper"
        data-params="{{ json_encode([
            'fetchLocationUrl' => route('dashboard.index.locations'),
            'canFullLocation' => Auth::user()->can('locations.full'),
            'canFullToilet' => Auth::user()->can('toilets.full'),
            'userLevel' => Auth::user()->role->level
        ]) }}"
        >
        @include('frontend.layouts.header')

        <v-side-button></v-side-button>

        @yield('content')

        @include('frontend.layouts.footer')

        @yield('modal')
    </div>
    @include('frontend.layouts.script')
    @yield('add-js')
</body>

</html>