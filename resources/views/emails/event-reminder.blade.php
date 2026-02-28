<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Reminder</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <h2>Hi {{ $attendeeName }},</h2>

    <p>This is a friendly reminder that you are registered for an event <strong>tomorrow</strong>:</p>

    <p><strong>{{ $event->title }}</strong></p>
    <p><strong>Date:</strong> {{ $event->date_time }}</p>
    <p><strong>Location:</strong> {{ $event->location }}</p>

    @if($event->description)
        <p>{{ $event->description }}</p>
    @endif

    <p>We look forward to seeing you there!</p>

    <p>Thank you,<br>EventHub</p>
</body>
</html>
