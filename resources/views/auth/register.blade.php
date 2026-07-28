<x-auth-shell title="Request staff access">
    <div class="auth-header">
        <p class="eyebrow">Staff portal</p>
        <h2>Request staff access</h2>
        <p>Requests require administrator activation before sign-in.</p>
    </div>

    <form method="POST" action="{{ route('register.store') }}" class="form-stack">
        @csrf
        <div class="form-grid">
            <label>First name<input name="fname" value="{{ old('fname') }}" required></label>
            <label>Middle name<input name="mname" value="{{ old('mname') }}"></label>
            <label>Last name<input name="lname" value="{{ old('lname') }}" required></label>
            <label>Username<input name="username" value="{{ old('username') }}" autocomplete="username" required></label>
        </div>
        <x-form-error field="fname" /><x-form-error field="mname" /><x-form-error field="lname" /><x-form-error field="username" />
        <label>Email address<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></label>
        <x-form-error field="email" />
        <label>Password<input type="password" name="password" autocomplete="new-password" required></label>
        <x-form-error field="password" />
        <label>Confirm password<input type="password" name="password_confirmation" autocomplete="new-password" required></label>
        <button type="submit" class="button primary">Submit access request</button>
    </form>

    <div class="auth-links"><a href="{{ route('login') }}">Return to sign in</a></div>
</x-auth-shell>
