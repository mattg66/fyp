<?php

namespace App\Http\Clients;

use GuzzleHttp\Client;
use App\Exceptions\APIClientException;
use App\Models\FabricNode;
use App\Models\InterfaceModel;
use App\Models\Project;
use App\Models\Rack;
use App\Models\VlanPool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IOSXEClient
{
    protected $client;
    protected $authToken;

    public function __construct($token = null, $ipAddr)
    {
        $this->client = new Client([
            'base_uri' => 'https://' . $ipAddr .  '/api/',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'verify' => false,
        ]);
        if ($token === null) {
            $this->connect();
        } else {
            $this->authToken = $token;
        }
    }
    protected function connect()
    {
        try {
            $response = $this->client->post('v1/auth/token-services', [
                'auth' => [
                    env('VSPHERE_CSRV_USERNAME'),
                    env('VSPHERE_CSRV_SECRET')
                ]
            ]);
            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody());
                $this->authToken = $data->{'token-id'};
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }
    
    public function setHostname($hostname)
    {
        
    }
}
