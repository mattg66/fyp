<?php

namespace App\Http\Controllers;

use App\Models\Label;
use App\Models\Node;
use App\Models\Rack;
use App\Models\TerminalServer;
use App\Models\ToR;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NodeController extends Controller
{
    use ValidatesRequests;
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
    public function create(Request $request)
    {
        $this->validate($request, [
            'label' => 'required|max:50',
            'x' => 'required|numeric',
            'y' => 'required|numeric',
            'type' => 'required',
            'ts_id' => 'numeric',
            'tor_id' => 'numeric',
        ]);
        if ($request->type == 'rackNode') {
            try {
                DB::beginTransaction();

                $node = new Node();
                $node->x = $request->x;
                $node->y = $request->y;
                $node->save();

                $rack = new Rack();
                $rack->label = $request->label;
                $rack->node_id = $node->id;
                $rack->save();
                $rack_id = $rack->id;

                if ($request->has('ts_id')) {
                    $ts = TerminalServer::find($request->ts_id);
                    if ($ts == null) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Terminal Server not found',
                        ], 404);
                    }
                    $ts->racks()->attach($rack_id);
                }
                if ($request->has('tor_id')) {
                    $tor = ToR::find($request->tor_id);
                    if ($tor == null) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'ToR not found',
                        ], 404);
                    }
                    $tor->racks()->attach($rack_id);
                }
                DB::commit();
                return response()->json([
                    'message' => 'Rack created',
                    'id' => $rack->id,
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Rack creation failed',
                ], 500);
            }
        } else if ($request->type == 'labelNode') {
            try {
                DB::beginTransaction();

                $node = new Node();
                $node->x = $request->x;
                $node->y = $request->y;
                $node->save();

                $label = new Label();
                $label->label = $request->label;
                $label->node_id = $node->id;
                $label->save();

                DB::commit();
                return response()->json([
                    'message' => 'Label created',
                    'id' => $label->id,
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Label creation failed',
                ], 500);
            }
        } else {
            return response()->json([
                'message' => 'Invalid type',
            ], 400);
        }
    }


    public function getById($id)
    {
        $node = Node::find($id);
        if ($node == null) {
            return response()->json([
                'message' => 'Node not found',
            ], 404);
        }
        return response()->json($node);
    }
    public function getAll()
    {
        $nodes = Node::with('rack', 'label')->get();
        foreach ($nodes as $node) {
            if ($node->label == null) {
                unset($node->label);
            }
            if ($node->rack == null) {
                unset($node->rack);
            }
        }
        return response()->json($nodes);
    }
    public function updateById(Request $request, $id)
    {
        $this->validate($request, [
            'label' => 'max:50',
            'x' => 'numeric',
            'y' => 'numeric',
            'ts_id' => 'numeric',
            'tor_id' => 'numeric',
        ]);
        $node = Node::with('rack', 'label')->find($id);
        if ($node == null) {
            return response()->json([
                'message' => 'Node not found',
            ], 404);
        }
        DB::beginTransaction();
        if ($request->has('x') && $request->has('y')) {
            $node->x = $request->x;
            $node->y = $request->y;
        }
        if ($request->has('label')) {
            if ($node->label != null) {
                $node->label->label = $request->label;
                $node->label->save();
            } else if ($node->rack != null) {
                $node->rack->label = $request->label;
                $node->rack->save();
            }
        }
        if ($request->has('tor_id')) {
            $tor = ToR::find($request->tor_id);
            if ($tor == null) {
                return response()->json([
                    'message' => 'ToR not found',
                ], 404);
            }
            if ($node->rack != null) {
                $node->rack->tors()->detach();
                $node->rack->tors()->attach($tor->id);
            }
        }
        if ($request->has('ts_id')){
            $ts = TerminalServer::find($request->ts_id);
            if ($ts == null) {
                return response()->json([
                    'message' => 'Terminal Server not found',
                ], 404);
            }
            if ($node->rack != null) {
                $node->rack->terminalServers()->detach();
                $node->rack->terminalServers()->attach($ts->id);
            }
        }
        $node->save();
        DB::commit();
        return response()->json([
            'message' => 'Node updated',
        ], 200);
    }
    public function deleteById($id)
    {
        $node = Node::find($id);
        if ($node == null) {
            return response()->json([
                'message' => 'Node not found',
            ], 404);
        }
        $node->delete();
        return response()->json([
            'message' => 'Node deleted',
        ], 200);
    }
}
