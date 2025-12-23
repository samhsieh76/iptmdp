@extends('backend.layouts.app')
@section('content')
    <v-sensor-show ref="list"
        :sensor_type="$t('human_traffic_sensors')"
        :sensor_menus="{{ json_encode($sensor_menus) }}"
        :thead='[
            $t("latest_data"),
            $t("people_number"),
            $t("updated_at"),
            $t("delete"),
        ]'
        :fields='["raw_data", "value", "created_at", "delete_options"]'
        :location="{{ json_encode($location) }}"
        :toilet="{{ json_encode($toilet) }}"
        :sensor="{{ json_encode($sensor) }}"
        :search-log-options="{{ json_encode(trans('messages.search_log_options')) }}"

        url="{{ route('human_traffic_logs.index.data', [$toilet->id, $sensor->id]) }}"
        location-index-url="{{ route('locations.index') }}"
        toilet-index-url="{{ route('toilets.index', [$location->id]) }}"

        chart-data-url="{{ route('human_traffic_logs.index.chart_data', [$toilet->id, $sensor->id]) }}"
        chart-split-number=5

        :is-bar="true"

        @can('human_traffic_sensors.toggle_notification')
            :can-toggle-notification="true"
            toggle-notification-url="{{ route('human_traffic_sensors.toggle_notification', [$toilet->id,$sensor->id]) }}"
        @endcan

        {{-- @can('human_traffic_logs.create') :can-create="true" @endcan --}}
        @can('locations.index') :can-locations-index="true" @endcan
        @can('toilets.index') :can-toilets-index="true" @endcan
        @can('human_traffic_logs.download')
            :can-download="true"
            download-url="{{ route('human_traffic_logs.download', [$toilet->id, $sensor->id]) }}"
        @endcan

        @if(Auth::user()->can('human_traffic_sensors.index'))
            :can-toilets-show="true"
            toilet-show-url="{{ route('human_traffic_sensors.index', $toilet->id) }}"
        @elseif(Auth::user()->can('toilets.show'))
            :can-toilets-show="true"
            toilet-show-url="{{ route('toilets.show', ['location' => $location->id, 'toilet' => $toilet->id]) }}"
        @endif>
    </v-sensor-show>
@endsection

@section('modal')
    <v-sensor-log-modal ref="settingModal" store-url="{{ route('human_traffic_logs.store', [$toilet->id, $sensor->id]) }}"
        :location="{{ json_encode($location) }}"
        :toilet="{{ json_encode($toilet) }}"
        :sensor="{{ json_encode($sensor) }}"
        step="1"
        ></v-sensor-modal>
@endsection

@section('add-css')
<link rel="stylesheet" href="{{ asset('assets/css/backend/sensor_log.css') }}">
@endsection