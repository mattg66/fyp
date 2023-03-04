<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VlanPool extends Model
{
    use HasFactory;
    protected $fillable = [
        'dn', 'name', 'start', 'end', 'project_pool', 'alloc_mode', 'parent_dn'
    ];
}
