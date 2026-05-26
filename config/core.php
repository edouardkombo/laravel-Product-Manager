<?php

return [
    'entities' => [
        'product' => [
            'class' => \App\Models\Product::class,
            'rules' => [
                'name' => 'required|string',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
            ]
        ],
        'order' => [
            'class' => \App\Models\Order::class,
            'rules' => [
                'customer' => 'required|string',
                'status' => 'in:pending,paid,shipped',
            ]
        ],
        'item' => [
            'class' => \App\Models\Item::class,
            'rules' => [
                'order' => 'required|exists:order,id',
                'product' => 'required|exists:product,id',
                'quantity' => 'required|integer|min:1',
                'price' => 'required|numeric|min:0',
            ]
        ],
        'media' => [
            'class' => \App\Models\Media::class,
            'rules' => [
                'type' => 'required|string',
                'link' => 'required|integer',
                'path' => 'required|string',
            ]
        ],
        'user' => [
            'class' => \App\Models\User::class,
            'rules' => [
                'name' => 'required|string',
                'surname' => 'required|string',
                'email' => 'required|email|unique:user,email',
                'password' => 'required|string|min:6',
                'role' => 'in:admin,viewer',
            ]
        ],
    ]
];
