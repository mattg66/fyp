<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    use HasFactory;

    public function rack()
    {
        return $this->hasOne(Rack::class);
    }

    public function label()
    {
        return $this->hasOne(Label::class);
    }
}
