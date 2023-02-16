<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterfaceModel extends Model
{
    use HasFactory;
    
    public function fabricNode()
    {
        return $this->hasOne(FabricNode::class);
    }

    protected $hidden = [];
    protected $table = 'interfaces';
}
