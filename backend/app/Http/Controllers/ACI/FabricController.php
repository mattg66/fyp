<?php

namespace App\Http\Controllers\ACI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Clients\ACIClient;
use App\Models\FabricNode;
use App\Models\InterfaceModel;
use App\Models\Project;
use App\Models\VlanPool;

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
    public function getInterfaces($id)
    {
        $interfaces = InterfaceModel::where('fabric_node_id', '=', $id)->get();
        if ($interfaces->count() == 0) {
            return response()->json([
                'message' => 'No interfaces found',
            ], 404);
        }
        return response()->json(
            $interfaces
        );
    }
    public function getVlanPools()
    {
        $vlanPool = VlanPool::all();
        if ($vlanPool->count() == 0) {
            return response()->json([]);
        }
        return response()->json(
            $vlanPool
        );
    }
    public function setVlanPool(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|numeric',
        ]); 
        if (Project::count() != 0) {
            return response()->json([
                'message' => 'VLAN Pool in use, remove all projects first',
            ], 400);
        }
        $existing = VlanPool::where('project_pool', '=', true)->first();
        if ($existing != null) {
            $existing->project_pool = false;
            $existing->save();
        }
        $vlanPool = VlanPool::find($request->id);
        if ($vlanPool == null) {
            return response()->json([
                'message' => 'VLAN Pool not found',
            ], 404);
        }
        $vlanPool->project_pool = null;
        $vlanPool->save();
        return response()->json([
            'message' => 'VLAN Pool set',
        ]);
    }
}
