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
        DB::beginTransaction();
        $vlanPool = VlanPool::where('project_pool', true)->first();
        if (!$vlanPool) {
            DB::rollBack();
            return response()->json([
                'message' => 'No VLAN Pool has been set',
            ], 400);
        }
        $existingVlan = Vlan::orderBy('vlan_id', 'desc')->first();
        $project = new Project();
        if ($existingVlan == null) {
            $vlan = new Vlan();
            $vlan->vlan_id = $vlanPool->start;
            $vlan->vlan_pool_id = $vlanPool->id;
            $vlan->save();
            $project->vlan_id = $vlan->id;
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
                $vlan->save();
                $project->vlan_id = $vlan->id;
            }
        }
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
            $networks = Project::select('network', 'subnet_mask')
                ->whereRaw('INET_ATON(network) & INET_ATON(subnet_mask) = INET_ATON(network)')
                ->orderBy('network')
                ->get();

            $last_subnet = null;

            foreach ($networks as $network) {
                $subnet_parts = explode('.', $network->subnet_mask);
                if ($subnet_parts[0] == 255 && $subnet_parts[1] == 255) {
                    $last_subnet = $network;
                } else {
                    break;
                }
            }

            if (!$last_subnet) {
                $next_subnet = '10.0.0.0';
            } else {
                $last_subnet_parts = explode('.', $last_subnet->network);
                $next_subnet_parts = array_slice($last_subnet_parts, 1);
                $next_subnet_parts[0]++;
                $next_subnet = implode('.', array_merge(array_slice($last_subnet_parts, 0, 1), $next_subnet_parts));
            }
            $project->network = $next_subnet;
            $project->subnet_mask = '255.255.0.0';
        }

        $project->save();
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
    public function getAll()
    {
        $projects = Project::all();
        return response()->json($projects);
    }
    public function test()
    {
        $aciClient = new ACIClient();
        return response()->json($aciClient->getVlanPools());
    }
}
