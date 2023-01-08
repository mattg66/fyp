<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Clients\ACIClient;
use App\Http\Clients\vSphereClient;

class Test extends Controller
{
    public function getVersion(Request $request)
    {
        $client = new ACIClient();
        $version = $client->getApicVersion();

        return response()->json([
            'version' => $version,
        ]);
    }
    public function getVMs(Request $request)
    {
        $client = new vSphereClient();
        return response()->json([
            'VMs' => $client->getVmList(),
        ]);
    }
}
