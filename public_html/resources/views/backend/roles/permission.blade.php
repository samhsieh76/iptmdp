@extends('backend.layouts.app')

@section('content')
    <v-role-permissions action-url="{{ route('roles.permission.update', $role->id) }}" :role="{{ json_encode($role) }}"
        :programs="{{ json_encode($programs) }}" :role_permissions="{{ json_encode($role_permissions) }}">
        <template #breadcrumb>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item">
                    @can('locations.index')
                    <a href="{{ route('roles.index') }}">@{{ $t("role_management") }}</a>
                    @else
                    @{{ $t("role_management") }}
                    @endcan
                  </li>
                  <li class="breadcrumb-item active" aria-current="page">
                    {{ $role->name }}@{{ $t("role_permission") }}
                  </li>
                </ol>
              </nav>
        </template>
    </v-role-permissions>
@endsection
