<?php

namespace App\Http\Controllers;

use App\Http\Clients\ACIClient;
use App\Models\Project;
use App\Models\Rack;
use App\Models\Vlan;
use App\Models\VlanPool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    function validate_network($network_address, $subnet_mask)
    {
        $subnet_mask_binary = '';
        foreach (explode('.', $subnet_mask) as $part) {
            $subnet_mask_binary .= str_pad(decbin($part), 8, '0', STR_PAD_LEFT);
        }

        $subnet_mask_length = strlen(rtrim($subnet_mask_binary, '0'));

        if ($subnet_mask_length < 16) {
            return false;
        }

        $network_address_binary = '';
        foreach (explode('.', $network_address) as $part) {
            $network_address_binary .= str_pad(decbin($part), 8, '0', STR_PAD_LEFT);
        }

        return (bindec($network_address_binary) & bindec($subnet_mask_binary)) == bindec($network_address_binary);
    }
    public function calculateNextSubnet($projects)
    {
        $firstIp = explode('.', '10.0.0.0');
        for ($i = 0; $i < 256; $i++) {
            $exists = false;
            foreach ($projects as $project) {
                $projIp = explode('.', $project->network);
                if ($projIp[0] == $firstIp[0] && $projIp[1] == $i) {
                    $exists = true;
                }
            }
            if (!$exists) {
                return [
                    'network' => '10' . '.' . $i . '.0.0',
                    'subnet_mask' => '255.255.0.0',
                ];
            }
        }
    }
    public function create(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:50',
            'description' => 'required|max:500',
            'network' => 'ip|nullable',
            'subnet_mask' => 'ip|nullable',
            'racks' => 'array',
            'racks.*' => 'integer|exists:racks,id',
        ]);
        $vlanPool = VlanPool::where('project_pool', true)->first();
        if (!$vlanPool) {
            return response()->json([
                'message' => 'No VLAN Pool has been set',
            ], 400);
        }
        DB::beginTransaction();
        $existingVlan = Vlan::orderBy('vlan_id', 'desc')->first();
        $project = new Project();
        $project->name = $request->name;
        $project->description = $request->description;
        if ($request->network != null && $request->subnet_mask != null) {
            if (!$this->validate_network($request->network, $request->subnet_mask)) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Invalid network address',
                ], 400);
            }
            $project->network = $request->network;
            $project->subnet_mask = $request->subnet_mask;
        } else {
            $projects = Project::orderBy('network', 'asc')->get();
            $nextNetwork = $this->calculateNextSubnet($projects);
            $project->network = $nextNetwork['network'];
            $project->subnet_mask = '255.255.0.0';
            $project->save();
            if ($existingVlan == null) {
                $vlan = new Vlan();
                $vlan->vlan_id = $vlanPool->start;
                $vlan->vlan_pool_id = $vlanPool->id;
                $vlan->project_id = $project->id;
                $vlan->save();
            } else {
                if ($existingVlan->vlan_id + 1 == $vlanPool->end) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'No VLANs available',
                    ], 400);
                } else {
                    $vlan = new Vlan();
                    $vlan->vlan_id = $existingVlan->vlan_id + 1;
                    $vlan->vlan_pool_id = $vlanPool->id;
                    $vlan->project_id = $project->id;
                    $vlan->save();
                }
            }
            $racks = Rack::whereIn('id', $request->racks)->get();
            if ($request->racks) {
                $project->racks()->saveMany($racks);
            }
            DB::commit();
            return response()->json([
                'message' => 'Project created successfully',
                'project' => $project,
            ], 201);
        }
    }
    public function getAll()
    {
        $projects = Project::with('vlan')->get();
        return response()->json($projects);
    }
    public function deleteById($id)
    {
        $project = Project::find($id);
        if (!$project) {
            return response()->json([
                'message' => 'Project not found',
            ], 404);
        }
        $project->delete();
        return response()->json([
            'message' => 'Project deleted successfully',
        ], 200);
    }
    public function test()
    {
        $aciClient = new ACIClient();
        return response()->json($aciClient->getVlanPools());
    }
}
