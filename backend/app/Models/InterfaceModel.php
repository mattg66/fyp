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

    public function terminalServer()
    {
        return $this->hasOne(TerminalServer::class);
    }

    protected $hidden = [];
    protected $fillable = [
        'dn', 'aci_id', 'state', 'fabric_node_id'
    ];
    protected $table = 'interfaces';
}
