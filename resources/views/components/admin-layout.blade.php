<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} — {{ auth()->user()?->currentAgency()?->name ?? config('app.name') }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site antialiased">
    <div class="flex min-h-screen flex-col md:flex-row">
        <aside class="shrink-0 border-b border-line bg-navy text-white md:w-60 md:border-r md:border-b-0">
            <div class="flex items-center gap-3 px-5 py-5">
                <span class="brand-mark bg-white! text-navy!" aria-hidden="true">{{ mb_substr(auth()->user()?->currentAgency()?->name ?? 'F', 0, 1) }}</span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold">{{ auth()->user()?->currentAgency()?->name }}</span>
                    <span class="block text-xs text-white/60">Agency admin</span>
                </span>
            </div>
            <nav class="flex gap-1 overflow-x-auto px-3 pb-3 text-sm md:flex-col md:pb-0">
                @php
                    $navItems = [
                        ['route' => 'admin.dashboard', 'label' => 'Dashboard'],
                        ['route' => 'admin.properties.index', 'label' => 'Listings'],
                        ['route' => 'admin.viewings.index', 'label' => 'Viewings'],
                        ['route' => 'admin.knowledge-items.index', 'label' => 'Knowledge base'],
                        ['route' => 'admin.handovers.index', 'label' => 'Handovers'],
                    ];
                @endphp
                @foreach ($navItems as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        class="block shrink-0 rounded-xl px-3 py-2 {{ request()->routeIs(str_replace('.index', '', $item['route']).'*') ? 'bg-white text-navy font-semibold' : 'text-white/75 hover:bg-white/10' }}"
                    >{{ $item['label'] }}</a>
                @endforeach
            </nav>
            <form method="POST" action="{{ route('admin.logout') }}" class="hidden p-3 md:block">
                @csrf
                <button type="submit" class="w-full rounded-xl px-3 py-2 text-left text-sm text-white/60 hover:bg-white/10">Log out</button>
            </form>
        </aside>

        <main class="flex-1 overflow-y-auto">
            <div class="mx-auto max-w-6xl px-5 py-8">
                @if (session('status'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                    <h1 class="display text-4xl">{{ $title ?? 'Dashboard' }}</h1>
                    {{ $actions ?? '' }}
                </div>

                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
