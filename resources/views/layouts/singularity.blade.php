<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Core – {{ ucfirst($view ?? 'dashboard') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100">
    @auth
    <div class="flex h-screen">
        <aside class="w-64 bg-white shadow-md">
            <div class="p-4 font-bold text-lg border-b">⚡ Core</div>
            <nav class="mt-4">
                <a href="/admin?tab=product" class="block px-4 py-2 hover:bg-gray-100">📦 Products</a>
                <a href="/admin?tab=order" class="block px-4 py-2 hover:bg-gray-100">📋 Orders</a>
                <a href="/admin?tab=analytics" class="block px-4 py-2 hover:bg-gray-100">📊 Analytics</a>
                <form method="POST" action="/logout" class="block mt-4">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-red-600">🚪 Logout</button>
                </form>
            </nav>
        </aside>
        <main class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </main>
    </div>
    @else
        @yield('content')
    @endauth
    @livewireScripts
</body>
</html>
