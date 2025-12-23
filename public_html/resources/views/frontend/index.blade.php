@extends('frontend.layouts.app')

@section('content')
    <div class="content-wrapper" v-if="!loading">
        <div class="left-side">
            <v-site-selection location-toilet-url="{{ route('dashboard.index.toilets', 'location_id') }}"
                :toilet-type-options="{{ json_encode(trans('messages.toilet_type_options')) }}"></v-site-selection>
            <v-performance-score operate-data-url="{{ route('dashboard.index.operational_score') }}"
                process-data-url="{{ route('dashboard.index.process_score') }}"></v-performance-score>
            <v-map ref="map">
                <template #map>
                    @include('frontend.layouts.map')
                </template>
            </v-map>
        </div>
        <div class="right-side">
            <v-operational-location v-if="mode === modeMap.TAIWAN || mode === modeMap.COUNTY"
                :regions="{{ json_encode($regions) }}"
                :toilet-type-options="{{ json_encode(trans('messages.toilet_type_options')) }}"
                county-location-url="{{ route('dashboard.index.county_locations', 'county_id') }}"
                location-toilet-url="{{ route('dashboard.index.toilets', 'location_id') }}"></v-operational-location>
            <v-operational-today-chart v-if="mode === modeMap.TAIWAN || mode === modeMap.COUNTY">
            </v-operational-today-chart>
            <v-operational-monthly-chart v-if="mode === modeMap.TAIWAN || mode === modeMap.COUNTY">
            </v-operational-monthly-chart>
            <div class="section-block" ref="locationBlock">
                <v-location-info location-toilet-url="{{ route('dashboard.index.toilets', 'location_id') }}"
                    v-if="mode === modeMap.LOCATION || mode === modeMap.TOILET"></v-location-info>
            </div>
            <div class="section-block" ref="humanTrafficBlock">
                <v-human-traffic-block daily-url="{{ route('dashboard.index.human_traffic_daily_data') }}"
                    monthly-url="{{ route('dashboard.index.human_traffic_monthly_data') }}"
                    v-if="mode === modeMap.LOCATION || mode === modeMap.TOILET"></v-human-traffic-block>
            </div>
            <div class="section-block" ref="toiletPaperBlock">
                <v-toilet-paper-block data-url="{{ route('dashboard.index.toilet_paper_data') }}"
                    refill-data-url="{{ route('dashboard.index.toilet_paper_refill_data') }}"
                    v-if="mode === modeMap.LOCATION || mode === modeMap.TOILET"></v-toilet-paper-block>
            </div>
            <div class="section-block" ref="handLotionBlock">
                <v-hand-lotion-block data-url="{{ route('dashboard.index.hand_lotion_data') }}"
                    refill-data-url="{{ route('dashboard.index.hand_lotion_refill_data') }}"
                    v-if="mode === modeMap.LOCATION || mode === modeMap.TOILET"></v-hand-lotion-block>
            </div>
            <div class="section-block" ref="smellyBlock">
                <v-smelly-block data-url="{{ route('dashboard.index.smelly_data') }}"
                    v-if="mode === modeMap.LOCATION || mode === modeMap.TOILET"></v-smelly-block>
            </div>
            <div class="section-block" ref="tempHumidityBlock">
                <v-temp-humidity-block data-url="{{ route('dashboard.index.temp_humidity_data') }}"
                    v-if="mode === modeMap.LOCATION || mode === modeMap.TOILET"></v-temp-humidity-block>
            </div>
            <div class="section-block" ref="improveBlock">
                <v-improve-record data-url="{{ route('dashboard.index.improve_data') }}"
                    v-if="mode === modeMap.LOCATION || mode === modeMap.TOILET"></v-improve-record>
            </div>
            <div class="section-block" ref="abnormalBlock">
                <v-abnormal-record data-url="{{ route('dashboard.index.abnormal_data') }}"
                    v-if="mode === modeMap.LOCATION || mode === modeMap.TOILET"></v-abnormal-record>
            </div>
        </div>
    </div>
@endsection

@section('modal')
    <v-leave-modal ref="leaveModal" logout-url="{{ route('logout') }}"
        @if (Auth::user()->can('locations.index') || (Auth::user()->can('toilets.index') && count($fetch_locations) > 0))
            :can-view-data="true"
            view-data-url="{{ Auth::user()->can('locations.index') ? route('locations.index') : route('toilets.index', [$fetch_locations[0]->id]) }}"
        @endif
        @can('users.index')
            :can-manage-user="true"
            manage-user-url="{{ route('users.index') }}"
        @endcan
        guard="{{ Auth::getDefaultDriver() }}">
    </v-leave-modal>
@endsection

<script></script>
