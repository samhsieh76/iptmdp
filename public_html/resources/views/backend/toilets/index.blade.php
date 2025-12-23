@extends('backend.layouts.app')

@section('content')
    <v-toilet-list ref="list" url="{{ route('toilets.index.data', $location->id) }}"
        :type-options="{{ json_encode(trans('messages.toilet_type_options')) }}"
        @can('location_sensors.index')
        :can-sensor="true"
        sensors-url="{{ route('location_sensors.index', $location->id) }}"
        @endcan
        @can('toilets.create')
        :can-create="true"
        @endcan
        user-level="{{ Auth::user()->role->level }}">
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
                    <li class="breadcrumb-item active" aria-current="page">
                        {{ $location->name }}
                    </li>
                </ol>
            </nav>
        </template>
    </v-toilet-list>
@endsection

@section('modal')
    <v-toilet-modal ref="settingModal" store-url="{{ route('toilets.store', $location->id) }}"
        :type-options="{{ json_encode(trans('messages.toilet_type_options')) }}"
        :location="{{ json_encode($location) }}"></v-toilet-modal>
@endsection
