@extends('backend.layouts.app')

@section('content')
    <v-location-list ref="list"
        :county-options="{{ json_encode($countyOptions) }}"
        url="{{ route('locations.index.data') }}"
        @can('locations.create')
        :can-create="true"
        @endcan
        @can('locations.request_permission')
        request-url="{{ route('locations.request_permission') }}"
        :can-request="true"
        @endcan
        :locations="{{ json_encode($fetch_locations) }}"
    >
    </v-location-list>
@endsection

@section('modal')
    <v-location-modal ref="settingModal" :county-options="{{ json_encode($countyOptions) }}"
        :user-options="{{ json_encode($userOptions) }}"
        store-url="{{ route('locations.store') }}"
    >
    </v-location-modal>
    @can('locations.request_permission')
        <v-location-request-modal ref="settingRequestModal"
            url="{{ route('location_audit_records.index.data') }}"
            :status-options="{{ json_encode(trans('messages.location_audit_record_status_options')) }}"
        >
        </v-location-request-modal>
    @endcan
@endsection
