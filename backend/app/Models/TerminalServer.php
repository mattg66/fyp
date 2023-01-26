<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TerminalServer extends Model
{
    use HasFactory;

    public function rack()
    {
        return $this->belongsTo(Rack::class);
    }

    protected $hidden = ['password']; 
}
