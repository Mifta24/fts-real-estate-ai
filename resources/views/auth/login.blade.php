<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agency Admin · FTS Real Estate AI</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site grid min-h-screen place-items-center px-4">
    <div class="w-full max-w-sm rounded-3xl border border-line bg-white p-7 shadow-sm">
        <span class="brand"><span class="brand-mark" aria-hidden="true">F</span><span class="brand-name">FTS Real Estate AI</span></span>
        <h1 class="display mt-6 text-4xl">Agency admin</h1>
        <p class="mt-2 text-sm text-muted">Sign in to manage listings, viewings and your AI Property Advisor.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.store') }}" class="mt-6 space-y-4">
            @csrf
            <label class="wizard-field">
                <span>Email</span>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
            </label>
            <label class="wizard-field">
                <span>Password</span>
                <input id="password" type="password" name="password" required>
            </label>
            <label class="flex items-center gap-2 text-sm text-muted">
                <input type="checkbox" name="remember" class="rounded border-line">
                Remember me
            </label>
            <button type="submit" class="btn btn-dark w-full">Sign in</button>
        </form>
    </div>
</body>
</html>
