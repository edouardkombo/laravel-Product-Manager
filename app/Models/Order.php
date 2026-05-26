<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model 
{ 
    protected $table = 'order';             // <-- explicit singular table name
    protected $guarded = []; 
    public $timestamps = false; 
}
