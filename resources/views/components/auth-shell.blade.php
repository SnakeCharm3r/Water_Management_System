<x-layouts.portal :title="$title ?? config('app.name')">
    <main class="auth-shell">
        <section class="auth-brand">
            <p class="eyebrow">DAWASA</p>
            <h1>Water management workspace</h1>
            <p>Secure access for authorized water authority staff.</p>
        </section>
        <section class="auth-card">
            {{ $slot }}
        </section>
    </main>
</x-layouts.portal>
