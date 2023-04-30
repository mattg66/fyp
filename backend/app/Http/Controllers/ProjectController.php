<?php

namespace App\Http\Controllers;

use App\Http\Clients\ACIClient;
use App\Jobs\AddRack;
use App\Jobs\CreateProject;
use App\Jobs\DeleteProject;
use App\Jobs\DeleteRack;
use App\Models\Project;
use App\Models\ProjectRouter;
use App\Models\Rack;
use App\Models\Vlan;
use App\Models\VlanPool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    function validateNetworkSettings($ipAddress, $subnetMask, $gateway)
    {
        // Validate IP address
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            return false;
        }
        $subnetMaskLong = ip2long($subnetMask);

        $subnetMaskBinary = decbin($subnetMaskLong);
        if (!preg_match('/^1+0*$/', $subnetMaskBinary)) {
            return false;
        }
        // Validate subnet mask
        // Validate gateway
        if (!filter_var($gateway, FILTER_VALIDATE_IP)) {
            return false;
        }

        // Check if gateway is on the same subnet as IP address using subnet mask
        $ipLong = ip2long($ipAddress);
        $gatewayLong = ip2long($gateway);
        if (($ipLong & $subnetMaskLong) != ($gatewayLong & $subnetMaskLong)) {
            return false;
        }

        // All checks passed
        return true;
    }
    public function create(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:15|not_regex:/\s/',
            'description' => 'required|max:500',
            'network' => 'ip|nullable',
            'subnet_mask' => 'ip|nullable',
            'wan_ip' => 'ip|nullable',
            'wan_subnet_mask' => 'ip|nullable',
            'wan_gateway' => 'ip|nullable',
            'racks' => 'array',
            'racks.*' => 'integer|exists:racks,id',
        ]);
        $vlanPool = VlanPool::where('project_pool', true)->first();
        if (!$vlanPool) {
            return response()->json([
                'message' => 'No VLAN Pool has been set',
            ], 400);
        }
        if (!$this->validateNetworkSettings($request->wan_ip, $request->wan_subnet_mask, $request->wan_gateway)) {
            return response()->json([
                'message' => 'Invalid WAN settings',
            ], 400);
        }
        DB::beginTransaction();
        $existingVlan = Vlan::orderBy('vlan_id', 'desc')->first();
        if (Project::where('name', $request->name)->first() !== null) {
            return response()->json([
                'message' => 'Name must be unique between projects',
            ], 400);
        }
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
            $project->status = 'ACI';
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
            $projectRouter = new ProjectRouter();
            $projectRouter->project_id = $project->id;
            $projectRouter->ip = $request->wan_ip;
            $projectRouter->subnet_mask = $request->wan_subnet_mask;
            $projectRouter->gateway = $request->wan_gateway;
            $projectRouter->save();
            $temp = $project;
            $temp->projectRouter = $projectRouter;
            DB::commit();
            CreateProject::dispatch($project->name, $project->id);
            return response()->json([
                'message' => 'Project created successfully',
                'project' => $temp,
            ], 201);
        }
    }
    function findObjectById($array, $id)
    {
        foreach ($array as $element) {
            if ($id == $element->id) {
                return $element;
            }
        }
        return false;
    }
    public function updateById(Request $request, $id)
    {
        $this->validate($request, [
            'description' => 'required|max:500',
            'racks' => 'array',
            'racks.*' => 'integer|exists:racks,id',
        ]);
        $project = Project::with(['racks', 'vlan', 'projectRouter'])->find($id);
        if (!$project) {
            return response()->json([
                'message' => 'Project not found',
            ], 404);
        }
        DB::beginTransaction();
        $project->description = $request->description;
        $project->save();
        $removeRacks = [];
        $addRacks = [];

        foreach ($project->racks as $rack) {
            if (array_search($rack->id, $request->racks) === false) {
                DeleteRack::dispatch($id, $rack->id);
            }
        }
        foreach ($request->racks as $rack) {
            if (!$this->findObjectById($project->racks, $rack)) {
                $rack = Rack::find(Intval($rack));
                if ($rack->project_id !== null) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Rack already has project',
                    ], 403);
                }
                $rack->project_id = $id;
                $rack->save();
                AddRack::dispatch($id, $rack->id);
            }
        }
        DB::commit();
        return response()->json(['message' => 'Project updated successfully', 'project' => $project]);
    }
    public function getAll()
    {
        $projects = Project::with(['vlan', 'racks', 'projectRouter'])->get();
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
        if ($project->status === 'Provisioned') {
            $project->delete();
            DeleteProject::dispatch($project->name, $project->id);
            return response()->json([
                'message' => 'Project deleted successfully',
            ], 200);
        } else {
            return response()->json([
                'message' => 'Project still provisioning',
            ], 400); 
        }
    }
    public function test()
    {
        $aciClient = new ACIClient();
        return response()->json($aciClient->getVlanPools());
    }
    public function getStatus()
    {
        $project = Project::all(['status']);
        return response()->json(
            $project
        );
    }
}
