<x-auth-shell title="Choose a new password">
    <div class="auth-header">
        <p class="eyebrow">Staff portal</p>
        <h2>Choose a new password</h2>
        <p>Create a secure password to restore staff access.</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="form-stack">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label>Email address<input type="email" name="email" value="{{ old('email', $request->email) }}" autocomplete="email" required autofocus></label>
        <x-form-error field="email" />
        <label>New password<input type="password" name="password" autocomplete="new-password" required></label>
        <x-form-error field="password" />
        <label>Confirm new password<input type="password" name="password_confirmation" autocomplete="new-password" required></label>
        <button type="submit" class="button primary">Reset password</button>
    </form>
</x-auth-shell>
