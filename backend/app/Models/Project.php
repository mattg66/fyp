<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    public function racks()
    {
        return $this->hasMany(Rack::class);
    }
    public function vlan()
    {
        return $this->hasOne(Vlan::class);
    }
}
