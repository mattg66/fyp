<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FabricNode extends Model
{
    use HasFactory;

    public function rack()
    {
        return $this->belongsTo(Rack::class);
    }
    public function interfaces()
    {
        return $this->hasMany(InterfaceModel::class);
    }

    protected $hidden = [];
    protected $fillable = [
        'dn', 'aci_id', 'model', 'role', 'serial', 'description'
    ];
}
