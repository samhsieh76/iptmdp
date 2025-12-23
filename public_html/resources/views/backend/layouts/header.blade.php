<header>
    <div class="topbar">
        <div class="container">
            {{-- <div class="logo"></div> --}}
            <a href="{{ Auth::user()->can('locations.index') ? route('locations.index') : route('toilets.index', [$fetch_locations[0]->id]) }}" class="logo"></a>
            <div class="right-side">
                <v-clock></v-clock>
                <v-dropdown-userinfo :user="{{ json_encode(Auth::user()) }}"
                :locations="{{ json_encode($bind_locations) }}"
                password-url="{{ route("users.password.self") }}"
                logout-url="{{ route('logout') }}"
                guard="{{ Auth::getDefaultDriver() }}"
                ></v-dropdown-userinfo>
            </div>
        </div>{{--  /.container --}}
    </div>{{--  /.topbar --}}

    <div class="header-navigation" v-cloak>
        <div class="btn-back" v-if="hasHistory" v-on:click="handleHistoryBackClick"><i class="icon-back"></i></div>
        @can('toilets.index')
            <v-global-search :locations="{{ json_encode($fetch_locations) }}" toilet-url="{{ route('toilets.index', 'location_id') }}"></v-global-search>
        @endcan
        <ul class="menu">
            <li class="menu-item" v-for="(menu, index) in {{ json_encode($navbar_menus) }}">
                <a :class="`menu-link ${menu['active']?'active':''}`" :href="menu['path']">@{{ menu['name'] }}</a>
            </li>
        </ul>
    </div>{{--  /.header-navigation --}}
</header>