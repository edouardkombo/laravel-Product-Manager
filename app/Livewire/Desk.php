<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Kernel\Core;

class Desk extends Component
{
    use WithFileUploads;

    public $view = 'product';
    public $rows = [];
    public $name, $description, $price, $stock, $amend = null; // renamed property
    public $image;
    public $csv;

    protected $queryString = ['view'];
    protected $core;

    public function boot(Core $core)
    {
        $this->core = $core;
    }

    public function mount()
    {
        $this->view = request()->query('tab', 'product');
        $this->fetch();
    }

    public function setView($view)
    {
        $this->view = $view;
        $this->fetch();
    }

    public function fetch()
    {
        $c = $this->core;
        if ($this->view === 'product') {
            $this->rows = $c->extract('product', ['order' => 'created', 'dir' => 'desc']);
        } elseif ($this->view === 'order') {
            $this->rows = $c->extract('order', ['order' => 'date', 'dir' => 'desc']);
        } elseif ($this->view === 'item') {
            $this->rows = $c->extract('item', ['order' => 'id', 'dir' => 'desc']);
        } elseif ($this->view === 'analytics') {
            $orders = $c->extract('order', ['aggregate' => ['count' => true]])['value'] ?? 0;
            $items = $c->extract('item', ['aggregate' => ['sum' => 'quantity']])['value'] ?? 0;
            $revenue = $c->extract('item', ['aggregate' => ['sum' => 'price']])['value'] ?? 0;
            $top = $c->extract('item', ['aggregate' => ['group' => 'product', 'sum' => 'quantity'], 'take' => 5]);
            $this->rows = ['orders' => $orders, 'items' => $items, 'revenue' => $revenue, 'top' => is_array($top) ? $top : []];
        }
    }

    public function create()
    {
        $c = $this->core;
        $this->validate([
            'name' => 'required',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'image' => 'nullable|image|max:2048',
        ]);
        $product = $c->create('product', [
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'created' => now()
        ]);
        if ($this->image) {
            $path = $this->image->store('products', 'public');
            $c->create('media', [
                'type' => 'product',
                'link' => $product->id,
                'path' => $path,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->reset(['name', 'description', 'price', 'stock', 'image']);
        $this->fetch();
    }

    // Method name remains 'edit' (one word)
    public function edit($id)
    {
        $c = $this->core;
        $p = $c->read('product', $id);
        if ($p) {
            $this->amend = $id;      // store the ID in the renamed property
            $this->name = $p->name;
            $this->description = $p->description;
            $this->price = $p->price;
            $this->stock = $p->stock;
        }
    }

    public function update()
    {
        $c = $this->core;
        $this->validate([
            'name' => 'required',
            'price' => 'required|numeric',
            'stock' => 'required|integer'
        ]);
        $c->update('product', $this->amend, [
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock
        ]);
        $this->amend = null;
        $this->reset(['name', 'description', 'price', 'stock']);
        $this->fetch();
    }

    public function delete($id)
    {
        $c = $this->core;
        $media = $c->extract('media', ['where' => ['type' => 'product', 'link' => $id]]);
        foreach ($media as $m) {
            $c->delete('media', $m['id']);
        }
        $items = $c->extract('item', ['where' => ['product' => $id]]);
        foreach ($items as $item) {
            $c->delete('item', $item['id']);
        }
        $c->delete('product', $id);
        $this->fetch();
    }

    public function import()
    {
        $c = $this->core;
        $this->validate(['csv' => 'required|file|mimes:csv,txt']);
        $path = $this->csv->getRealPath();
        $handle = fopen($path, 'r');
        $head = fgetcsv($handle);
        if (!$head) {
            fclose($handle);
            session()->flash('msg', 'Empty CSV');
            return;
        }
        while (($row = fgetcsv($handle)) !== false) {
            if (count($head) !== count($row)) continue;
            $data = array_combine($head, $row);
            if (!isset($data['customer'], $data['product'], $data['quantity'])) continue;
            $product = $c->read('product', $data['product']);
            if (!$product) continue;
            $order = $c->create('order', ['customer' => $data['customer'], 'status' => 'pending']);
            $c->create('item', [
                'order' => $order->id,
                'product' => $data['product'],
                'quantity' => $data['quantity'],
                'price' => $product->price,
            ]);
        }
        fclose($handle);
        $this->fetch();
        session()->flash('msg', 'Imported successfully');
    }

    public function render()
    {
        return view('livewire.desk');
    }
}
