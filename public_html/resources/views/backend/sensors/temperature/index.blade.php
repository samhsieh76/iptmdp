@extends('backend.layouts.app')
@section('content')
    <v-sensor-list ref="list" url="{{ route('temperature_sensors.index.data', [$toilet->id]) }}"
        :sensor_menus="{{ json_encode($sensor_menus) }}"
        :show_menu_children="false"
        :thead='[
            "ID",
            $t("sensor_name"),
            $t("temperature"),
            $t("updated_at"),
            $t("action"),
        ]'
        :fields='["id", "name", "latest_value", "latest_updated_at"]'
        :value-unit="$t('temperature_unit')"
        @can('temperature_sensors.create') :can-create="true" @endcan>
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
    <v-sensor-modal ref="settingModal" store-url="{{ route('temperature_sensors.store', $toilet->id) }}"
        :location="{{ json_encode($location) }}" :toilet="{{ json_encode($toilet) }}"
        :type-options="{{ json_encode(trans('messages.toilet_type_options')) }}"
        :sensor_name='$t("temperature")'
        :extra-fields='[]'></v-sensor-modal>
@endsection