<?php

namespace App\Http\Controllers\ACI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Clients\ACIClient;
use App\Models\FabricNode;

class FabricController extends Controller
{

    public function getNodes(Request $request)
    {
        $nodes = [];
        if ($request->has('withoutRack')) {
            if ($request->has('rackId')) {
                $nodes = FabricNode::whereDoesntHave('rack')->orWhere('rack_id', '=', $request->rackId)->get();
            } else {
                $nodes = FabricNode::whereDoesntHave('rack')->get();
            }
        } else {
            $nodes = FabricNode::all();
        }
        return response()->json(
            $nodes
        );
    }
}
