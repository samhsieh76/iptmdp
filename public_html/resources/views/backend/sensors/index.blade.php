@extends('backend.layouts.app')

@section('content')
    <v-location-sensor-list
    :sensor-url='{
        "human_traffic": "{{ route('location_sensors.index.human_traffic_sensors', $location->id) }}",
        "toilet_paper": "{{ route('location_sensors.index.toilet_paper_sensors', $location->id) }}",
        "hand_lotion": "{{ route('location_sensors.index.hand_lotion_sensors', $location->id) }}",
        "smelly": "{{ route('location_sensors.index.smelly_sensors', $location->id) }}",
        "relative_humidity": "{{ route('location_sensors.index.relative_humidity_sensors', $location->id) }}",
        "temperature": "{{ route('location_sensors.index.temperature_sensors', $location->id) }}",
    }'
    user-level="{{ Auth::user()->role->level }}"
    :type-options="{{ json_encode(trans('messages.toilet_type_options')) }}"
    >
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
    </v-location-sensor-list>
@endsection