<x-layouts.app :title="config('app.name')">
    <main class="mx-auto flex min-h-screen max-w-5xl items-center px-6 py-16">
        <section class="space-y-6">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-700">DAWASA Water Management</p>
            <h1 class="max-w-3xl text-4xl font-semibold tracking-tight sm:text-6xl">Institutional operations, billing, and field-service management.</h1>
            <p class="max-w-2xl text-lg text-gray-600">Authorized staff can manage zone-based water services through the administration workspace.</p>
            <a href="{{ url('/admin') }}" class="inline-flex rounded-lg bg-amber-600 px-5 py-3 font-medium text-white shadow-sm hover:bg-amber-700">Staff sign in</a>
        </section>
    </main>
</x-layouts.app>
