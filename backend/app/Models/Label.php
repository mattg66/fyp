<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    use HasFactory;
    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    protected $hidden = ['node_id', 'id'];
}