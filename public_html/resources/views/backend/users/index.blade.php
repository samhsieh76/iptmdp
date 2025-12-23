@extends('backend.layouts.app')

@section('content')
    <v-user-list ref="list" url="{{ route('users.index.data') }}"
        @can('users.create')
        :can-create="true"
        @endcan
        :role-options="{{ json_encode($roleOptions) }}"
        ></v-user-list>
@endsection

@section('modal')
    <v-user-modal ref="settingModal" :role-options="{{ json_encode($roleOptions) }}" store-url="{{ route('users.store') }}"
        parent-url="{{ route('users.index.parents', 'role_id') }}"
        :role-parent-options="{{ json_encode($roleParentOptions) }}"></v-user-modal>
    @can('users.password')
        <v-user-password-modal ref="settingPasswordModal" />
    @endcan
@endsection
