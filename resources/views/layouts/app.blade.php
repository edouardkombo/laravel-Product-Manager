<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Core Admin – @yield('title')</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @livewireStyles
    <style>
        body { background: #f8f9fc; }
        .sidebar { min-height: 100vh; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        .sidebar .nav-link { color: #333; border-radius: 0; padding: 0.75rem 1.5rem; }
        .sidebar .nav-link:hover { background: #f0f2f5; }
        .sidebar .nav-link.active { background: #0d6efd; color: white; }
        .card-stat { transition: transform 0.2s; }
        .card-stat:hover { transform: translateY(-5px); }
    </style>
</head>
<body>
    @auth
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar p-0">
                <div class="p-3 border-bottom">
                    <h4>⚡ Core Admin</h4>
                </div>
                <div class="nav flex-column">
                    <a href="/admin?tab=product" class="nav-link {{ request('tab') == 'product' ? 'active' : '' }}">
                        📦 Products
                    </a>
                    <a href="/admin?tab=order" class="nav-link {{ request('tab') == 'order' ? 'active' : '' }}">
                        📋 Orders
                    </a>
                    <a href="/admin?tab=item" class="nav-link {{ request('tab') == 'item' ? 'active' : '' }}">
                        🧾 Order Items
                    </a>
                    <a href="/admin?tab=analytics" class="nav-link {{ request('tab') == 'analytics' ? 'active' : '' }}">
                        📊 Analytics
                    </a>
                    <hr>
                    <form method="POST" action="/logout" class="mt-3">
                        @csrf
                        <button type="submit" class="nav-link text-danger bg-transparent border-0 w-100 text-start">🚪 Logout</button>
                    </form>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                @yield('content')
            </main>
        </div>
    </div>
    @else
        @yield('content')
    @endauth

    @livewireScripts
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
