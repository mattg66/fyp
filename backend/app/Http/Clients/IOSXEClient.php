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
            'base_uri' => 'https://' . $ipAddr .  ':1025/api/',
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
    function getHighestUsableIPAddress($networkAddress, $subnetMask)
    {
        // Convert network address and subnet mask to long integers
        $networkAddressLong = ip2long($networkAddress);
        $subnetMaskLong = ip2long($subnetMask);

        // Calculate the broadcast address (last address in the subnet)
        $broadcastAddressLong = $networkAddressLong | (~$subnetMaskLong & 0xFFFFFFFF);

        // Calculate the highest usable IP address (one less than the broadcast address)
        $highestUsableIPAddressLong = $broadcastAddressLong - 1;

        // Convert the highest usable IP address back to dotted decimal notation
        $highestUsableIPAddress = long2ip($highestUsableIPAddressLong);

        return $highestUsableIPAddress;
    }

    public function setHostname($hostname)
    {
        try {
            $response = $this->client->put('v1/global/host-name', [
                'headers' => [
                    'X-auth-token' => $this->authToken,
                ],
                'json' => [
                    'host-name' => $hostname
                ]
            ]);
            if ($response->getStatusCode() == 200) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }

    public function setAddresses($wanIp, $subnetMask, $lanNetwork, $lanMask)
    {
        try {
            $response = $this->client->put('v1/interfaces/gigabitEthernet2', [
                'headers' => [
                    'X-auth-token' => $this->authToken,
                ],
                'json' => [
                    "type" => "ethernet",
                    "if-name" => "gigabitEthernet2",
                    "description" => "WAN",
                    "ip-address" => $wanIp,
                    "subnet-mask" => $subnetMask,
                    "nat-direction" => "outside"
                ]
            ]);
            if ($response->getStatusCode() == 204 && $this->setLanIp($this->getHighestUsableIPAddress($lanNetwork, $lanMask), $lanMask)) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }

    function setLANIp($lanIp, $subnetMask)
    {
        try {
            $response = $this->client->put('v1/interfaces/gigabitEthernet1', [
                'headers' => [
                    'X-auth-token' => $this->authToken,
                ],
                'json' => [
                    "type" => "ethernet",
                    "if-name" => "gigabitEthernet1",
                    "description" => "LAN",
                    "ip-address" => $lanIp,
                    "subnet-mask" => $subnetMask,
                    "nat-direction" => "inside"
                ]
            ]);
            if ($response->getStatusCode() == 204) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }

    public function setStaticRoute($wanGateway)
    {
        try {
            $response = $this->client->post('v1/routing-svc/static-routes', [
                'headers' => [
                    'X-auth-token' => $this->authToken,
                ],
                'json' => [
                    'destination-network' => '0.0.0.0/0',
                    'next-hop-router' => $wanGateway,
                ]
            ]);
            if ($response->getStatusCode() == 201) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }
}
