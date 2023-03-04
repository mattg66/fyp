<?php

namespace App\Http\Controllers;

use App\Http\Clients\ACIClient;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    function validate_network($network_address, $subnet_mask)
    {
        // Convert subnet mask to binary
        $subnet_mask_binary = '';
        foreach (explode('.', $subnet_mask) as $part) {
            $subnet_mask_binary .= str_pad(decbin($part), 8, '0', STR_PAD_LEFT);
        }

        // Count number of consecutive 1s in binary subnet mask
        $subnet_mask_length = strlen(rtrim($subnet_mask_binary, '0'));

        if ($subnet_mask_length < 16) {
            return false;
        }

        // Convert network address to binary
        $network_address_binary = '';
        foreach (explode('.', $network_address) as $part) {
            $network_address_binary .= str_pad(decbin($part), 8, '0', STR_PAD_LEFT);
        }

        // Check if network address is within subnet
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
            'racks.*' => 'number',
        ]);
        $project = new Project();
        $project->name = $request->name;
        $project->description = $request->description;
        if ($request->network != null && $request->subnet_mask != null) {
            if (!$this->validate_network($request->network, $request->subnet_mask)) {
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
                // No /16 subnet found, return default
                $next_subnet = '10.0.0.0';
            } else {
                $last_subnet_parts = explode('.', $last_subnet->network);
                $next_subnet_parts = array_slice($last_subnet_parts, 2);

                // Increment third octet by 1
                $next_subnet_parts[0]++;

                $next_subnet = implode('.', array_merge(array_slice($last_subnet_parts, 0, 2), $next_subnet_parts));
            }
            $project->network = $next_subnet;
            $project->subnet_mask = '255.255.0.0';
        }
        
        $project->save();
    }
    public function test() {
        $aciClient = new ACIClient();
        return response()->json($aciClient->getVlanPools());
    }
}
