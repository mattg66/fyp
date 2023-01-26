<?php

namespace App\Http\Controllers\ACI;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Clients\ACIClient;

class StatusController extends Controller
{
    public function getStatus()
    {
        $client = new ACIClient();
        $version = $client->getApicVersion();
        $health = $client->getHealth();
        $fabricHealth = $client->getFabricHealth();
        return response()->json([
            'version' => $version,
            'health' => $health,
            'fabricNodes' => $fabricHealth,
        ]);
    }
    public function getVersion()
    {
        $client = new ACIClient();
        $version = $client->getApicVersion();

        return response()->json([
            'version' => $version,
        ]);
    }
    public function getHealth()
    {
        $client = new ACIClient();
        $health = $client->getHealth();

        return response()->json([
            'health' => $health,
        ]);
    }
}
