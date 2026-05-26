<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Product extends Model 
{ 
    protected $table = 'product';           // <-- explicit singular table name
    protected $guarded = []; 
    public $timestamps = false; 
}
