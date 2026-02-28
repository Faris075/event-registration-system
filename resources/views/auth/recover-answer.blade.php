<x-guest-layout>
    <div class="auth-card">
        <h2 class="auth-title">Answer Security Question</h2>
        <p class="auth-subtitle" style="margin-bottom:1.5rem;">Answer your security question to prove it's you.</p>

        <p class="auth-label" style="margin-bottom:1rem;font-size:1rem;color:var(--text);">{{ $question }}</p>

        <form method="POST" action="{{ route('security-question.verify') }}" class="auth-form">
            @csrf

            <div class="auth-field">
                <label for="security_answer" class="auth-label">Your Answer</label>
                <input id="security_answer" type="text" name="security_answer" value="{{ old('security_answer') }}"
                       required autocomplete="off" placeholder="Answer (case-insensitive)" class="auth-input" />
                @error('security_answer')
                    <div class="auth-error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="auth-btn">Verify Answer</button>
        </form>

        <p class="auth-center">
            <a href="{{ route('security-question.recover') }}" class="auth-link">← Use a different email</a>
        </p>
    </div>
</x-guest-layout>
