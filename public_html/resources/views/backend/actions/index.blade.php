@extends('backend.layouts.app')

@section('content')
    <v-action-list ref="list"
        url="{{ route('actions.index.data') }}"
        @can('actions.create')
        :can-create="true"
        @endcan
    >
    </v-action-list>
@endsection

@section('modal')
    <v-action-modal ref="settingModal"
        store-url="{{ route('actions.store') }}"
    >
    </v-action-modal>
@endsection
