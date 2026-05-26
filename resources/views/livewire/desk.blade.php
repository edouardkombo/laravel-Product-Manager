<div>
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <button class="nav-link {{ $view === 'product' ? 'active' : '' }}" wire:click="setView('product')">Products</button>
        </li>
        <li class="nav-item">
            <button class="nav-link {{ $view === 'order' ? 'active' : '' }}" wire:click="setView('order')">Orders</button>
        </li>
        <li class="nav-item">
            <button class="nav-link {{ $view === 'item' ? 'active' : '' }}" wire:click="setView('item')">Order Items</button>
        </li>
        <li class="nav-item">
            <button class="nav-link {{ $view === 'analytics' ? 'active' : '' }}" wire:click="setView('analytics')">Analytics</button>
        </li>
    </ul>

    <!-- ==================== PRODUCTS ==================== -->
    @if($view === 'product')
        <div class="card mb-4">
            <div class="card-header">Add Product</div>
            <div class="card-body">
                <form wire:submit.prevent="create" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" wire:model="name" placeholder="Name">
                    </div>
                    <div class="col-md-4">
                        <textarea class="form-control" wire:model="description" placeholder="Description"></textarea>
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" class="form-control" wire:model="price" placeholder="Price">
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control" wire:model="stock" placeholder="Stock">
                    </div>
                    <div class="col-md-6">
                        <input type="file" class="form-control" wire:model="image">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Create Product</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Product List</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Image</th><th>Name</th><th>Description</th><th>Price</th><th>Stock</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $p)
                        <tr>
                            <td>
                                @php
                                    $media = app(App\Kernel\Core::class)->extract('media', ['where' => ['type' => 'product', 'link' => $p['id']]]);
                                    $img = $media[0]['path'] ?? null;
                                @endphp
                                @if($img)
                                    @if(preg_match('/^https?:\/\//', $img))
                                        <img src="{{ $img }}" width="50" height="50" class="rounded" style="object-fit: cover;">
                                    @else
                                        <img src="{{ asset('storage/' . $img) }}" width="50" height="50" class="rounded" style="object-fit: cover;">
                                    @endif
                                @else
                                    <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width:50px;height:50px;">No</div>
                                @endif
                            </td>
                            <td>{{ $p['name'] }}</td>
                            <td>{{ $p['description'] ?? '' }}</td>
                            <td>${{ number_format($p['price'],2) }}</td>
                            <td>{{ $p['stock'] }}</td>
                            <td>
                                <button wire:click="edit({{ $p['id'] }})" class="btn btn-sm btn-outline-primary">Edit</button>
                                <button wire:click="delete({{ $p['id'] }})" class="btn btn-sm btn-outline-danger">Delete</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Edit Modal (using $amend property) -->
        @if($amend)
        <div class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Product</h5>
                        <button type="button" class="btn-close" wire:click="$set('amend', null)"></button>
                    </div>
                    <div class="modal-body">
                        <input type="text" class="form-control mb-2" wire:model="name" placeholder="Name">
                        <textarea class="form-control mb-2" wire:model="description" placeholder="Description"></textarea>
                        <input type="number" step="0.01" class="form-control mb-2" wire:model="price" placeholder="Price">
                        <input type="number" class="form-control mb-2" wire:model="stock" placeholder="Stock">
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" wire:click="$set('amend', null)">Cancel</button>
                        <button class="btn btn-primary" wire:click="update">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endif

    <!-- ==================== ORDERS ==================== -->
    @if($view === 'order')
        <div class="card mb-4">
            <div class="card-header">Import Orders (CSV)</div>
            <div class="card-body">
                <form wire:submit.prevent="import" class="row g-3">
                    <div class="col-auto">
                        <input type="file" class="form-control" wire:model="csv">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Upload CSV</button>
                    </div>
                </form>
                @if(session('msg')) <div class="alert alert-success mt-2">{{ session('msg') }}</div> @endif
            </div>
        </div>
        <div class="card">
            <div class="card-header">Orders</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>ID</th><th>Customer</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($rows as $o)
                        <tr>
                            <td>{{ $o['id'] }}</td>
                            <td>{{ $o['customer'] }}</td>
                            <td>{{ \Carbon\Carbon::parse($o['date'])->format('Y-m-d H:i') }}</td>
                            <td><span class="badge bg-info">{{ $o['status'] }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ==================== ITEMS ==================== -->
    @if($view === 'item')
        <div class="card">
            <div class="card-header">Order Items</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Order ID</th><th>Product</th><th>Quantity</th><th>Price</th><th>Total</th></tr></thead>
                    <tbody>
                        @foreach($rows as $i)
                        @php
                            $product = app(App\Kernel\Core::class)->read('product', $i['product']);
                            $productName = $product ? $product->name : 'Deleted product';
                        @endphp
                        <tr>
                            <td>{{ $i['order'] }}</td>
                            <td>{{ $productName }}</td>
                            <td>{{ $i['quantity'] }}</td>
                            <td>${{ number_format($i['price'],2) }}</td>
                            <td>${{ number_format($i['quantity'] * $i['price'],2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ==================== ANALYTICS ==================== -->
    @if($view === 'analytics')
        @php
            $totalOrders = $rows['orders'] ?? 0;
            $totalItems = $rows['items'] ?? 0;
            $totalRevenue = $rows['revenue'] ?? 0;
            $topProducts = is_array($rows['top'] ?? null) ? $rows['top'] : [];
        @endphp

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5>Total Orders</h5>
                        <p class="display-4">{{ $totalOrders }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5>Items Sold</h5>
                        <p class="display-4">{{ $totalItems }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5>Revenue</h5>
                        <p class="display-4">${{ number_format($totalRevenue, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Top 5 Selling Products</div>
            <div class="card-body">
                <canvas id="topProductsChart" width="400" height="200"></canvas>
                <script>
                    document.addEventListener('livewire:initialized', function () {
                        const canvas = document.getElementById('topProductsChart');
                        if (!canvas) return;
                        const ctx = canvas.getContext('2d');
                        const topData = @json($topProducts);
                        if (topData && Array.isArray(topData) && topData.length > 0) {
                            const labels = topData.map(t => 'Product ' + t.product);
                            const data = topData.map(t => t.total);
                            if (window.topProductsChart && typeof window.topProductsChart.destroy === 'function') {
                                window.topProductsChart.destroy();
                            }
                            window.topProductsChart = new Chart(ctx, {
                                type: 'bar',
                                data: { labels: labels, datasets: [{ label: 'Quantity Sold', data: data, backgroundColor: '#0d6efd' }] },
                                options: { responsive: true, scales: { y: { beginAtZero: true } } }
                            });
                        } else {
                            if (window.topProductsChart && typeof window.topProductsChart.destroy === 'function') {
                                window.topProductsChart.destroy();
                                window.topProductsChart = null;
                            }
                            ctx.fillStyle = '#f8f9fa';
                            ctx.fillRect(0, 0, canvas.width, canvas.height);
                            ctx.fillStyle = '#6c757d';
                            ctx.font = '16px sans-serif';
                            ctx.fillText('No sales data available', canvas.width/2 - 100, canvas.height/2);
                        }
                    });
                </script>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Top Products List</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr><th>Product ID</th><th>Quantity Sold</th></tr>
                    </thead>
                    <tbody>
                        @forelse($topProducts as $t)
                        <tr>
                            <td>{{ $t['product'] ?? '?' }}</td>
                            <td>{{ $t['total'] ?? 0 }}</td>
                        </tr>
                        @empty
                        <td><td colspan="2">No sales data yet. (Run seeder first)</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
