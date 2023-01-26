<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rack extends Model
{
    use HasFactory;

    public function racks()
    {
        return $this->hasMany(Rack::class);
    }

    public function labels()
    {
        return $this->hasMany(Label::class);
    }
    
    public function terminalServer()
    {
        return $this->hasOne(TerminalServer::class);
    }

    public function tor()
    {
        return $this->hasOne(Tor::class);
    }
}
