<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You've been confirmed!</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <h2>Hi {{ $attendeeName }},</h2>

    <p>Great news! A spot has opened up and you have been <strong>promoted from the waitlist</strong> to a confirmed attendee for:</p>

    <p><strong>{{ $event->title }}</strong></p>
    <p><strong>Date:</strong> {{ $event->date_time }}</p>
    <p><strong>Location:</strong> {{ $event->location }}</p>

    <p>Your registration is now confirmed. We look forward to seeing you there!</p>

    <p>Thank you,<br>EventHub</p>
</body>
</html>
