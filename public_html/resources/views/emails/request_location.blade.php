@component('mail::message')
<h1>{{ trans('emails.request_location_title') }}</h1>
<span style="color: #4428B0; font-size: 1.1rem">{{ $user->name }}</span>向您{{ trans('emails.request_location_subject') }}
<br>
{{-- @if($record->status == 0) --}}
請問您是否同意授權{{ $location->name }}給<span style="color: #4428B0">{{ $user->name }}</span>?
<div class="btn-layout">
    <a class="btn btn-reject" href="{{ route('locations.accept') }}?token={{$record->token}}&accept=0&auditor_id={{$administrator->id}}">拒絕</a>
    <a class="btn btn-ok" href="{{ route('locations.accept') }}?token={{$record->token}}&accept=1&auditor_id={{$administrator->id}}">接受</a>
</div>
{{-- @elseif($record->status == 1)
您已同意授權{{ $location->name }}給{{ $user->name }}
@else
您已拒絕授權{{ $location->name }}給{{ $user->name }}
@endif --}}
@endcomponent