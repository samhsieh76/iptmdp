@component('mail::message')
<span>{!! trans('emails.user_instruction_content', ['username' => $user->username, 'password' => $password]) !!}</span>
@endcomponent