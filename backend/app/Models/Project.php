<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function racks()
    {
        return $this->hasMany(Rack::class);
    }
    public function vlan()
    {
        return $this->hasOne(Vlan::class);
    }
    public function projectRouter()
    {
        return $this->hasOne(ProjectRouter::class);
    }
}
