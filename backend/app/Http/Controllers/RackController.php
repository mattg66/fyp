<?php

namespace App\Http\Controllers;

use App\Models\Label;
use App\Models\Node;
use App\Models\Rack;
use App\Models\TerminalServer;
use App\Models\ToR;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RackController extends Controller
{
    function getAll(Request $request)
    {
        $racks = Rack::all();
        if ($request->has('withoutTS')) {
            if ($request->has('rackId')) {
                foreach ($racks as $key => $rack) {
                    if ($rack->terminalServer != null && $rack->id == $request->rackId) {
                        $racks->forget($key);
                    }
                }
            } else {
                foreach ($racks as $key => $rack) {
                    if ($rack->terminalServer != null) {
                        $racks->forget($key);
                    }
                }
            }
        }
        return response()->json($racks);
    }
}
