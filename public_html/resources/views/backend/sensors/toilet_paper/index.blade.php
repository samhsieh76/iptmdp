@extends('backend.layouts.app')
@section('content')
    <v-sensor-list ref="list" url="{{ route('toilet_paper_sensors.index.data', [$toilet->id]) }}"
        :sensor_menus="{{ json_encode($sensor_menus) }}"
        :show_menu_children="false"
        @can('toilet_paper_sensors.toggle_notification')
            :can-toggle-notification="true"
            toggle-notification-url="{{ route('toilet_paper_sensors.toggle_notification', [$toilet->id, 'sensor_id']) }}"
        @endcan
        :thead='[
            "ID",
            $t("sensor_name"),
            $t("latest_data"),
            $t("remaining_percentage"),
            $t("min_value"),
            $t("max_value"),
            $t("critical_value"),
            $t("notification_times"),
            $t("is_notification"),
            $t("updated_at"),
            $t("action"),
        ]'
        :fields='["id", "name", "latest_raw_data", "latest_value", "min_value", "max_value", "critical_value", "notification_times", "is_notification", "latest_updated_at"]'
        value-unit="%"
        @can('toilet_paper_sensors.create') :can-create="true" @endcan>
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
    <v-sensor-modal ref="settingModal" store-url="{{ route('toilet_paper_sensors.store', $toilet->id) }}"
        :location="{{ json_encode($location) }}" :toilet="{{ json_encode($toilet) }}"
        :type-options="{{ json_encode(trans('messages.toilet_type_options')) }}"
        :sensor_name='$t("toilet_paper")'
        :extra-fields='[
            {
                "name": "is_notification",
                "type": "notification",
                "required": true,
                "default": true
            },
            {
                "name": "min_value",
                "type": "number",
                "required": true
            }, {
                "name": "max_value",
                "type": "number",
                "required": true
            }
        ]'></v-sensor-modal>
@endsection
