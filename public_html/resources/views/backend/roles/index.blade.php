@extends('backend.layouts.app')

@section('content')
    <v-role-list ref="list" url="{{ route('roles.index.data') }}"
    @can('roles.create')
        :can-create="true"
    @endcan></v-role-list>
@endsection

@section('modal')
    <v-role-modal ref="settingModal" :role-group-options="{{ json_encode($roleGroupOptions) }}"
        store-url="{{ route('roles.store') }}"></v-role-modal>
@endsection
