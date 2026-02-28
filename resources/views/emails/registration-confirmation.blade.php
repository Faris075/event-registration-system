<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Confirmation</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <h2>Hi {{ $attendeeName }},</h2>

    @if($registrationStatus === 'confirmed')
        <p>Your registration is confirmed for <strong>{{ $event->title }}</strong>.</p>
    @else
        <p>You are registered for <strong>{{ $event->title }}</strong> and currently on the waitlist.</p>
        <p>Your waitlist position is <strong>{{ $waitlistPosition }}</strong>.</p>
    @endif

    <p><strong>Date:</strong> {{ $event->date_time }}</p>
    <p><strong>Location:</strong> {{ $event->location }}</p>

    <p>Thank you for registering.</p>
</body>
</html>
