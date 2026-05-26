<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
class User extends Authenticatable 
{ 
    protected $table = 'user';              // <-- explicit singular table name
    protected $guarded = []; 
}
