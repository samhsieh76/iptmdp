@extends('backend.layouts.app')
@section('content')
    <v-sensor-show ref="list"
        :sensor_type="$t('relative_humidity_sensors')"
        :sensor_menus="{{ json_encode($sensor_menus) }}"
        :thead='[
            $t("sensor_upload") + $t("relative_humidity"),
            $t("updated_at"),
            $t("delete"),
        ]'
        :fields='["value", "created_at", "delete_options"]'
        :location="{{ json_encode($location) }}"
        :toilet="{{ json_encode($toilet) }}"
        :sensor="{{ json_encode($sensor) }}"
        :search-log-options="{{ json_encode(trans('messages.search_log_options')) }}"
        :order-target="1"

        url="{{ route('relative_humidity_logs.index.data', [$toilet->id, $sensor->id]) }}"
        location-index-url="{{ route('locations.index') }}"
        toilet-index-url="{{ route('toilets.index', [$location->id]) }}"

        chart-data-url="{{ route('relative_humidity_logs.index.chart_data', [$toilet->id, $sensor->id]) }}"
        chart-unit="%"
        chart-max=100


        @can('relative_humidity_sensors.toggle_notification')
            :can-toggle-notification="true"
            toggle-notification-url="{{ route('relative_humidity_sensors.toggle_notification', [$toilet->id,$sensor->id]) }}"
        @endcan

        {{-- @can('relative_humidity_logs.create') :can-create="true" @endcan --}}
        @can('locations.index') :can-locations-index="true" @endcan
        @can('toilets.index') :can-toilets-index="true" @endcan
        @can('relative_humidity_logs.download')
            :can-download="true"
            download-url="{{ route('relative_humidity_logs.download', [$toilet->id, $sensor->id]) }}"
        @endcan

        @if(Auth::user()->can('relative_humidity_sensors.index'))
            :can-toilets-show="true"
            toilet-show-url="{{ route('relative_humidity_sensors.index', $toilet->id) }}"
        @elseif(Auth::user()->can('toilets.show'))
            :can-toilets-show="true"
            toilet-show-url="{{ route('toilets.show', ['location' => $location->id, 'toilet' => $toilet->id]) }}"
        @endif>
    </v-sensor-show>
@endsection

@section('modal')
    <v-sensor-log-modal ref="settingModal" store-url="{{ route('relative_humidity_logs.store', [$toilet->id, $sensor->id]) }}"
        :location="{{ json_encode($location) }}"
        :toilet="{{ json_encode($toilet) }}"
        :sensor="{{ json_encode($sensor) }}"
        step="0.01"
        ></v-sensor-modal>
@endsection

@section('add-css')
<link rel="stylesheet" href="{{ asset('assets/css/backend/sensor_log.css') }}">
@endsection