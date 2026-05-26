<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Item extends Model 
{ 
    protected $table = 'item';              // <-- explicit singular table name
    protected $guarded = []; 
    public $timestamps = false; 
}
