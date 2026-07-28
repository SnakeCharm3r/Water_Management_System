<x-auth-shell title="Staff sign in">
    <div class="auth-header">
        <p class="eyebrow">Staff portal</p>
        <h2>Sign in to your account</h2>
        <p>Use your staff username or email address.</p>
    </div>

    @if (session('status'))
        <div class="alert success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="form-stack">
        @csrf
        <label>Username or email address
            <input name="login" value="{{ old('login') }}" autocomplete="username" required autofocus>
        </label>
        <x-form-error field="login" />
        <label>Password
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <x-form-error field="password" />
        <label class="checkbox-row"><input type="checkbox" name="remember" value="1"> Remember me</label>
        <button type="submit" class="button primary">Sign in</button>
    </form>

    <div class="auth-links">
        <a href="{{ route('password.request') }}">Forgot password?</a>
        <a href="{{ route('register') }}">Request staff access</a>
    </div>
</x-auth-shell>
