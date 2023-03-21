<?php

namespace App\Http\Controllers\VSphere;

use App\Http\Clients\SSHClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Clients\vSphereClient;
use App\Models\Project;

class StatusController extends Controller
{
    public function getStatus()
    {
        $client = new vSphereClient();
        $serviceStatus = $client->getServiceStatus();
        $healthScore = 0;
        foreach ($serviceStatus as $service) {
            if ($service['state'] == 'STARTED') {
                $healthScore++;
            }
        }
        return response()->json([
            'services' => $serviceStatus,
            'health' => ($healthScore / count($serviceStatus)) * 100,
        ]);
    }
    public function test()
    {
        $client = new SSHClient();
        $vc = new vSphereClient();
        $project = Project::where('name', 'testagain')->first();
        return response()->json($client->provisionCSR($project, $vc->getVmIp('vm-5014')));
    }
}
