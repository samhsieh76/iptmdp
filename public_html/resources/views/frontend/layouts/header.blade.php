<header v-if="!loading">
    <div class="topbar">
        <div class="container">
            <div class="logo" @click.stop="handleBack"></div>
            <div class="toolbox">
                <div class="bg"></div>
                <div class="border-left"></div>
                <div class="border-right"></div>
                <v-search-box toilet-url="{{ route('toilets.index', 'location_id') }}"></v-search-box>
                <v-clock></v-clock>
            </div>
        </div>{{--  /.container --}}
    </div>{{--  /.topbar --}}
</header>
