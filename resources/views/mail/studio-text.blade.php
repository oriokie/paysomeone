{{ $heading }}

@foreach ($lines as $line)
{{ $line }}

@endforeach
@if ($actionUrl)
{{ $actionLabel }}: {{ $actionUrl }}
@endif

@if ($essential)
This message is about your account security or a payment. It is sent even if you have turned other email off.
@else
Email preferences: {{ $preferencesUrl }}
@endif
