<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    {{-- Base Meta Tags --}}
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
    <div id="app" class="container-fluid h-70" v-cloak>
        <div class="row h-100">
            <div class="login-container col-10 mx-auto">
                <div class="row login-leftside">
                    <img class="logo" src="{{ asset('images/login_logo.svg') }}">
                    <img class="logo" src="{{ asset('images/login_bg.svg') }}">
                </div>
                <div class="row login-box">
                    <div class="col-12 welcome-text">
                        <span v-html="$t('login_welcom_text')"></span>
                    </div>
                    <div class="col-12 login-form">
                        <form action="{{ route('login') }}" method="post">
                            {{ csrf_field() }}
                            <div class="col-12 row login-text">
                                <span>@{{ $t('login_text') }}</span>
                            </div>
                            <div class="row input-groups">
                                <div class="input-group">
                                    <span>@{{ $t('login_username') }}</span>
                                    <input type="text" name="username"
                                        class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}"
                                        value="{{ old('username') }}" autofocus="autofocus" autocomplete="username">
                                    @if ($errors->has('username'))
                                        <div class="invalid-feedback">
                                            <strong>{{ $errors->first('username') }}</strong>
                                        </div>
                                    @endif
                                </div>
                                <div class="input-group">
                                    <span>@{{ $t('login_password') }}</span>
                                    <input type="password" name="password"
                                        class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" autocomplete="current-password">
                                    @if ($errors->has('password'))
                                        <div class="invalid-feedback">
                                            <strong>{{ $errors->first('password') }}</strong>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="row login-btn">
                                <button type=submit class="btn btn-login">@{{ $t('login') }}</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-12 size-info">
                        <span>@{{ $t('login_suggestion') }}</span>
                        <span class="size">@{{ $t('login_size_suggestion') }}</span>
                        <span>@{{ $t('login_best_view') }}</span>
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
    <script>
        window.addEventListener('pageshow', function(event) {
            // 檢查 event.persisted 屬性來確認是否是從瀏覽器緩存中載入的頁面
            if (event.persisted) {
                // 重新整理登入頁面
                window.location.reload();
            }
        });
    </script>
</body>

</html>
