<?php

namespace App\Http\Controllers;

use App\Models\Node;
use App\Models\Rack;
use App\Models\TerminalServer;
use App\Models\ToR;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NodeController extends Controller
{
    public function getStatus()
    {
        // $client = new ACIClient();
        // $version = $client->getApicVersion();
        // $health = $client->getHealth();
        // $fabricHealth = $client->getFabricHealth();
        // return response()->json([
        //     'version' => $version,
        //     'health' => $health,
        //     'fabricNodes' => $fabricHealth,
        // ]);
    }
    public function createRack(Request $request)
    {
        $this->validate($request, [
            'label' => 'required|max:50',
            'x' => 'required|numeric',
            'y' => 'required|numeric',
            'ts_id' => 'numeric',
            'tor_id' => 'numeric',
        ]);
        DB::beginTransaction();

        $node = new Node();
        $node->x = $request->x;
        $node->y = $request->y;
        $node->save();

        $rack = new Rack();
        $rack->label = $request->label;
        $rack->node_id = $node->id;
        $rack->save();

        $ts = TerminalServer::find($request->ts_id);
        if ($ts->isEmpty()) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terminal Server not found',
            ], 404);
        }
        $tor = ToR::find($request->tor_id);
        if ($tor->isEmpty()) {
            DB::rollBack();
            return response()->json([
                'message' => 'ToR not found',
            ], 404);
        }
        $rack_id = $rack->id();
        $ts->racks()->attach($rack_id);
        $tor->racks()->attach($rack_id);
        DB::commit();
        return response()->json([
            'message' => 'Rack created',
        ], 201);
    }
    public function getAll()
    {
        return response()->json(Node::all());
    }
}
