<?php

namespace App\Http\Controllers\VSphere;

use App\Http\Clients\SSHClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Clients\vSphereClient;
use App\Jobs\TSProvision;
use App\Jobs\VirtualRouterProvision;
use App\Models\Project;
use Carbon\Carbon;

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
}
