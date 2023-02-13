<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rack extends Model
{
    use HasFactory;


    public function terminalServer()
    {
        return $this->hasOne(TerminalServer::class);
    }

    public function tor()
    {
        return $this->hasOne(Tor::class);
    }

    public function node()
    {
        return $this->belongsTo(Node::class);
    }
    
    public function fabricNode()
    {
        return $this->hasOne(FabricNode::class);
    }

    protected $hidden = [];
}
