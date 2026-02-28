<x-guest-layout>
    <div class="auth-card">
        <h2 class="auth-title">Set Security Question</h2>
        <p class="auth-subtitle">This helps you recover your account if you forget your password. Please set one now.</p>

        <form method="POST" action="{{ route('security-question.update') }}" class="auth-form">
            @csrf
            @method('PATCH')

            <div class="auth-field">
                <label for="security_question" class="auth-label">Security Question</label>
                <select id="security_question" name="security_question" class="auth-input" required>
                    <option value="">— Select a question —</option>
                    @foreach($questions as $q)
                        <option value="{{ $q }}" {{ old('security_question', $user->security_question ?? '') === $q ? 'selected' : '' }}>{{ $q }}</option>
                    @endforeach
                </select>
                @error('security_question')
                    <div class="auth-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth-field">
                <label for="security_answer" class="auth-label">Your Answer</label>
                <input id="security_answer" type="text" name="security_answer" value="{{ old('security_answer') }}"
                       required autocomplete="off" placeholder="Answer (case-insensitive)" class="auth-input" />
                @error('security_answer')
                    <div class="auth-error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="auth-btn">Save Security Question</button>
        </form>
    </div>
</x-guest-layout>
