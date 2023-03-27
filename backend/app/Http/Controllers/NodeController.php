<?php

namespace App\Http\Controllers;

use App\Models\FabricNode;
use App\Models\Label;
use App\Models\Node;
use App\Models\Rack;
use App\Models\TerminalServer;
use App\Models\ToR;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NodeController extends Controller
{
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
                    $ts->rack()->save($rack_id);
                }
                if ($request->has('fn_id')) {
                    $fn = FabricNode::find($request->fn_id);
                    if ($fn == null) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Fabric Node not found',
                        ], 404);
                    }
                    $fn->rack()->save($rack_id);
                }
                DB::commit();
                return response()->json([
                    'message' => 'Rack created',
                    'id' => $node->id,
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
                    'id' => $node->id,
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
        $nodes = Node::with('rack', 'label', 'rack.terminalServer', 'rack.fabricNode', 'rack.project')->get();
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
            'ts_id' => 'numeric|nullable',
            'fn_id' => 'numeric|nullable',
        ]);
        $node = Node::with('rack', 'label')->find($id);
        if ($node->rack->project_id !== null || ($request->has('x') && $request->has('y'))) {
            return response()->json([
                'message' => 'Rack has project assigned',
            ], 400);
        }
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
        if (($request->has('fn_id') || $request->has('ts_id')) && $node->rack->project_id !== null) {
            DB::rollBack();
            return response()->json([
                'message' => 'Cannot change Terminal Server or Fabric Node of a rack with a project',
            ], 400);
        }
        if ($request->has('fn_id')) {
            if ($request->fn_id == null) {
                if ($node->rack != null) {
                    FabricNode::where('rack_id', $node->rack->id)->update(['rack_id' => null]);
                }
            } else {
                $fn = FabricNode::find($request->fn_id);
                if ($fn == null) {
                    return response()->json([
                        'message' => 'Fabric Node not found',
                    ], 404);
                }
                if ($node->rack != null) {
                    if ($fn->rack_id != null && $fn->rack_id != $node->rack->id) {
                        return response()->json([
                            'message' => 'Fabric Node already assigned',
                        ], 400);
                    }
                    if ($node->rack->fabricNode !== null && $node->rack->fabricNode->id != $fn->id){
                        FabricNode::where('rack_id', $node->rack->id)->update(['rack_id' => null]);
                    }
                    $node->rack->fabricNode()->save($fn);
                }
            }
        }
        if ($request->has('ts_id')) {
            if ($request->ts_id == null) {
                if ($node->rack != null) {
                    TerminalServer::where('rack_id', $node->rack->id)->update(['rack_id' => null]);
                }
            } else {
                $ts = TerminalServer::find($request->ts_id);
                if ($ts == null) {
                    return response()->json([
                        'message' => 'Terminal Server not found',
                    ], 404);
                }
                if ($node->rack != null) {
                    if ($node->rack->terminalServer != null && $node->rack->terminalServer->id != $ts->id) {
                        return response()->json([
                            'message' => 'Terminal Server already assigned',
                        ], 400);
                    }
                    if ($node->rack->terminalServer !== null && $node->rack->terminalServer->id != $ts->id){
                        TerminalServer::where('rack_id', $node->rack->id)->update(['rack_id' => null]);
                    }
                    $node->rack->terminalServer()->save($ts);
                }
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
        $node = Node::with('rack')->find($id);
        if ($node == null) {
            return response()->json([
                'message' => 'Node not found',
            ], 404);
        }
        if ($node->rack->project_id !== null) {
            return response()->json([
                'message' => 'Rack has project assigned',
            ], 400);
        }
        $node->delete();
        return response()->json([
            'message' => 'Node deleted',
        ], 200);
    }
}
