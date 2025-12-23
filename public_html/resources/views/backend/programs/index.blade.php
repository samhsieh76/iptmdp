@extends('backend.layouts.app')

@section('content')
    <v-program-list ref="list" url="{{ route('programs.index.data') }}"
        @can('programs.create')
            :can-create="true"
        @endcan>
    </v-program-list>
@endsection

@section('modal')
    <v-program-modal ref="settingModal" :action-options="{{ json_encode($actionOptions) }}"
        store-url="{{ route('programs.store') }}">
    </v-program-modal>
@endsection
