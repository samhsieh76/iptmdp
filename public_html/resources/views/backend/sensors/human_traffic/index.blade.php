@extends('backend.layouts.app')
@section('content')
    <v-sensor-list ref="list" url="{{ route('human_traffic_sensors.index.data', [$toilet->id]) }}"
        :sensor_menus="{{ json_encode($sensor_menus) }}"
        :show_menu_children="false"
        @can('human_traffic_sensors.toggle_notification')
            :can-toggle-notification="true"
            toggle-notification-url="{{ route('human_traffic_sensors.toggle_notification', [$toilet->id, 'sensor_id']) }}"
        @endcan
        :thead='[
            "ID",
            $t("sensor_name"),
            $t("people_number"),
            $t("critical_value"),
            $t("deviation_value"),
            $t("notification_times"),
            $t("is_notification"),
            $t("updated_at"),
            $t("action"),
        ]'
        :fields='["id", "name", "latest_value", "critical_value", "min_value", "notification_times", "is_notification", "latest_updated_at"]'
        @can('human_traffic_sensors.create') :can-create="true" @endcan>
        <template #breadcrumb>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        @can('locations.index')
                            <a href="{{ route('locations.index') }}">@{{ $t("backend") }}</a>
                        @else
                            @{{ $t("backend") }}
                        @endcan
                    </li>
                    <li class="breadcrumb-item" aria-current="page">
                        @can('locations.index')
                            <a href="{{ route('toilets.index', [$location->id]) }}">{{ $location->name }}</a>
                        @else
                            {{ $location->name }}
                        @endcan
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        {{ $toilet->name }}-{{ trans('messages.toilet_type_options')[$toilet->type] }}
                    </li>
                </ol>
            </nav>
        </template>
    </v-sensor-list>
@endsection

@section('modal')
    <v-sensor-modal ref="settingModal" store-url="{{ route('human_traffic_sensors.store', $toilet->id) }}"
        :location="{{ json_encode($location) }}" :toilet="{{ json_encode($toilet) }}"
        :type-options="{{ json_encode(trans('messages.toilet_type_options')) }}"
        :sensor_name='$t("human")'
        :extra-fields='[
            {
                "name": "is_notification",
                "type": "notification",
                "required": true,
                "default": true
            },
            {
                "name": "critical_value",
                "type": "number",
                "required": true
            },
            {
                "name": "min_value",
                "display_name": $t("deviation_value"),
                "type": "number",
                "required": true
            }
        ]'></v-sensor-modal>
@endsection