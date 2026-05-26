<?php
use App\Kernel\Core;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/{entity}/create', fn($entity, Request $r, Core $c) => response()->json($c->create($entity, $r->all())));
Route::get('/{entity}/read/{id}', fn($entity, $id, Core $c) => response()->json($c->read($entity, $id)));
Route::put('/{entity}/update/{id}', fn($entity, $id, Request $r, Core $c) => response()->json($c->update($entity, $id, $r->all())));
Route::delete('/{entity}/delete/{id}', fn($entity, $id, Core $c) => response()->json(['deleted' => $c->delete($entity, $id)]));
Route::get('/{entity}/verify/{id}', fn($entity, $id, Request $r, Core $c) => response()->json(['verified' => $c->verify($entity, $id, $r->query('field'), $r->query('value'))]));
Route::get('/{entity}/extract', fn($entity, Request $r, Core $c) => response()->json($c->extract($entity, $r->query('spec') ? json_decode($r->query('spec'), true) : [])));
