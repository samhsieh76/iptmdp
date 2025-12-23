@extends('backend.layouts.app')

@section('content')
    <v-toilet-dashboard :location="{{ json_encode($location) }}" :toilet="{{ json_encode($toilet) }}"
        :sensor-counts="{{ json_encode($sensorCounts) }}"
        :type-options="{{ json_encode(trans('messages.toilet_type_options')) }}"
        toilet-paper-data-url="{{ route('daily.toilet_paper_data', $toilet->id) }}"
        human-traffic-data-url="{{ route('daily.human_traffic_data', $toilet->id) }}"
        smelly-data-url="{{ route('daily.smelly_data', $toilet->id) }}"
        hand-lotion-data-url="{{ route('daily.hand_lotion_data', $toilet->id) }}"
        temp-humidity-data-url="{{ route('daily.temp_humidity_data', $toilet->id) }}"
        >

    </v-toilet-dashboard>
@endsection

@section('add-css')
    <link rel="stylesheet" href="{{ asset('assets/css/backend/dashboard.css') }}">
@endsection

@section('modal')
@endsection
