@extends('layouts.app')

@section('content')
<div class="page-wrap" style="max-width:800px;">
    <div class="page-header">
        <h1 class="page-title">Terms &amp; Conditions</h1>
        <p class="page-subtitle">Last updated: {{ date('F Y') }}</p>
    </div>

    <div class="card" style="padding:2rem;">
        <h2 style="font-size:1.1rem;font-weight:700;margin-top:0;">1. Acceptance of Terms</h2>
        <p style="color:var(--muted);line-height:1.7;">By registering for any event on this platform, you agree to be bound by these Terms &amp; Conditions. If you do not agree, you must not register for or attend any events.</p>

        <h2 style="font-size:1.1rem;font-weight:700;margin-top:1.5rem;">2. Registration &amp; Payment</h2>
        <p style="color:var(--muted);line-height:1.7;">All registration fees are charged at the time of booking. Prices are displayed in USD and include applicable taxes where stated. Your registration is confirmed only upon successful payment and receipt of a confirmation email.</p>

        <h2 style="font-size:1.1rem;font-weight:700;margin-top:1.5rem;">3. Cancellation &amp; Refunds</h2>
        <p style="color:var(--muted);line-height:1.7;">You may cancel your registration at any time through your dashboard. If your payment status is <strong>Paid</strong>, a full refund will be processed within 5–7 business days to your original payment method. Refunds are not available after the event date has passed.</p>

        <h2 style="font-size:1.1rem;font-weight:700;margin-top:1.5rem;">4. Waitlist</h2>
        <p style="color:var(--muted);line-height:1.7;">If an event is fully booked, you may be added to a waitlist. If a spot becomes available, you will be automatically promoted to confirmed status and notified by email.</p>

        <h2 style="font-size:1.1rem;font-weight:700;margin-top:1.5rem;">5. Event Changes &amp; Cancellations</h2>
        <p style="color:var(--muted);line-height:1.7;">We reserve the right to cancel, postpone, or modify any event. In the event of cancellation by the organiser, all registered attendees will be notified by email and a full refund will be issued where applicable.</p>

        <h2 style="font-size:1.1rem;font-weight:700;margin-top:1.5rem;">6. Privacy &amp; Data</h2>
        <p style="color:var(--muted);line-height:1.7;">Your personal information (name, email, phone) is collected solely for the purpose of managing your event registration. It will not be sold or shared with third parties without your consent.</p>

        <h2 style="font-size:1.1rem;font-weight:700;margin-top:1.5rem;">7. Code of Conduct</h2>
        <p style="color:var(--muted);line-height:1.7;">All attendees are expected to behave respectfully. We reserve the right to remove any attendee who engages in disruptive or inappropriate behaviour without issuing a refund.</p>

        <h2 style="font-size:1.1rem;font-weight:700;margin-top:1.5rem;">8. Liability</h2>
        <p style="color:var(--muted);line-height:1.7;">The event organiser shall not be liable for any indirect, incidental, or consequential damages arising from attendance at or cancellation of any event. Your maximum remedy is a refund of the registration fee.</p>

        <h2 style="font-size:1.1rem;font-weight:700;margin-top:1.5rem;">9. Governing Law</h2>
        <p style="color:var(--muted);line-height:1.7;">These terms are governed by and construed in accordance with applicable local law. Any disputes shall be resolved in the courts of the relevant jurisdiction.</p>

        <div style="margin-top:2rem;padding-top:1.5rem;border-top:1px solid var(--border);">
            <a href="{{ url()->previous() }}" class="btn btn-ghost">← Go Back</a>
        </div>
    </div>
</div>
@endsection
