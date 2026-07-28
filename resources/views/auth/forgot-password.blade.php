<x-auth-shell title="Forgot password">
    <div class="auth-header">
        <p class="eyebrow">Staff portal</p>
        <h2>Reset your password</h2>
        <p>Enter your staff email address and we will send a reset link.</p>
    </div>

    @if (session('status'))
        <div class="alert success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="form-stack">
        @csrf
        <label>Email address<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus></label>
        <x-form-error field="email" />
        <button type="submit" class="button primary">Email password reset link</button>
    </form>

    <div class="auth-links"><a href="{{ route('login') }}">Return to sign in</a></div>
</x-auth-shell>
