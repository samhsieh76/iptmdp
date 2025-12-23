@extends('backend.layouts.app')

@section('content')
    <v-api_and_serve-list ref="list"
        url="{{ route('api_and_serves.index.data') }}"
        permission-url="{{ route('api_and_serves.update.permission')}}"
        :status-options="{{ json_encode(trans('messages.location_supplier_status_options')) }}"
        @can('api_and_serves.edit')
            :can-edit="true"
        @endcan
        @can('api_and_serves.download')
            :can-download="true"
            download-url="{{ route('api_and_serves.download') }}"
        @endcan
        @can('locations.request_permission')
            request-url="{{ route('locations.request_permission') }}"
            :can-request="true"
        @endcan
        user-level="{{ Auth::user()->role->level }}"
    >
    </v-api_and_serve-list>
@endsection

@section('modal')
    @can('locations.request_permission')
        <v-location-request-modal ref="settingRequestModal"
            url="{{ route('location_audit_records.index.data') }}"
            :status-options="{{ json_encode(trans('messages.location_audit_record_status_options')) }}"
        >
        </v-location-request-modal>
    @endcan
@endsection
