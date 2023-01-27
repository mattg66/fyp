<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToR extends Model
{
    public function rack()
    {
        return $this->belongsTo(Rack::class);
    }
    
    use HasFactory;
    protected $table = 'tors';
}
