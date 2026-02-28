<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
        .container { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .header { background: #ef4444; color: #fff; padding: 28px 36px; }
        .header h1 { margin: 0; font-size: 1.4rem; }
        .body { padding: 28px 36px; }
        .body p { color: #374151; line-height: 1.6; }
        .event-box { background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 16px 20px; margin: 20px 0; }
        .event-box p { margin: 4px 0; }
        .footer { padding: 16px 36px; border-top: 1px solid #e5e7eb; font-size: 0.82rem; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>❌ Event Cancelled</h1>
        </div>
        <div class="body">
            <p>Hi <strong>{{ $attendeeName }}</strong>,</p>
            <p>We regret to inform you that the following event has been <strong>cancelled</strong>:</p>
            <div class="event-box">
                <p><strong>{{ $event->title }}</strong></p>
                <p>📅 {{ \Carbon\Carbon::parse($event->date_time)->format('D, M d Y · g:i A') }}</p>
                <p>📍 {{ $event->location }}</p>
            </div>
            <p>Your registration status was: <strong>{{ ucfirst($registrationStatus) }}</strong>.</p>
            @if($registrationStatus === 'confirmed')
                <p>If you made a payment, a <strong>full refund</strong> will be processed within 5–7 business days. We sincerely apologise for any inconvenience.</p>
            @else
                <p>Since you were on the waitlist, no payment was taken. We apologise for the inconvenience.</p>
            @endif
            <p>We hope to see you at a future event.</p>
            <p>— The Events Team</p>
        </div>
        <div class="footer">
            This email was sent because you registered for {{ $event->title }}.
        </div>
    </div>
</body>
</html>
