<?php

namespace App\Http\Clients;

use App\Exceptions\APIClientException;
use App\Models\ProjectRouter;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class vSphereClient
{

    protected $client;
    protected $authToken;

    public function __construct()
    {
        if (env('VSPHERE_IPADDR') == null || env('VSPHERE_USERNAME') == null || env('VSPHERE_PASSWORD') == null) {
            throw new APIClientException('vSphere credentials not set');
        }
        $this->client = new Client([
            'base_uri' => 'https://' . env('VSPHERE_IPADDR') . '/api/',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'verify' => false,
        ]);

        $this->connect();
    }

    protected function connect()
    {
        try {
            $response = $this->client->post('session', [
                'auth' => [
                    env('VSPHERE_USERNAME'),
                    env('VSPHERE_PASSWORD'),
                ],
            ]);
        } catch (\Exception $e) {
            throw new APIClientException('Unable to connect to VSphere');
        }

        $responseData = json_decode($response->getBody(), true);
        $this->authToken = $responseData;
        return true;
    }

    public function getServiceStatus()
    {
        try {
            $response = $this->client->get('vcenter/services', [
                'headers' => [
                    'vmware-api-session-id' => $this->authToken,
                ],
            ]);
        } catch (\Exception $e) {
            throw new APIClientException('Unable to retrieve vSphere status');
        }
        $responseData = json_decode($response->getBody(), true);
        $services = [];
        foreach ($responseData as $key => $service) {
            $service['name'] = $key;
            array_push($services, $service);
        }
        return $services;
    }

    public function getVmList()
    {
        try {
            $response = $this->client->get('vcenter/vm', [
                'headers' => [
                    'vmware-api-session-id' => $this->authToken,
                ],
            ]);
        } catch (\Exception $e) {
            throw new APIClientException('Unable to retreive list of VMs');
        }

        $responseData = json_decode($response->getBody(), true);

        $vmList = [];
        foreach ($responseData as $vm) {
            $vmList[] = [
                'id' => $vm['vm'],
                'name' => $vm['name'],
            ];
        }

        return $vmList;
    }
    public function getTemplates()
    {
        try {
            $response = $this->client->get('vcenter/vm', [
                'headers' => [
                    'vmware-api-session-id' => $this->authToken,
                ],
            ]);
            $responseData = json_decode($response->getBody(), true);
            foreach ($responseData as $vm) {
                if ($vm['name'] === env('VSPHERE_PROJECT_ROUTER_VM_NAME') && $vm['power_state'] === 'POWERED_OFF') {
                    return $vm['vm'];
                }
            }
        } catch (\Exception $e) {
            throw new APIClientException('Unable to retreive list of VMs');
        }
    }
    public function deployProjectRouter($projectName, $projectId)
    {
        $vmId = $this->getTemplates();
        $vmName = env('VSPHERE_PROJECT_ROUTER_VM_NAME') . '-' . $projectName;
        $vmSpec = [
            'name' => $vmName,
            'source' => $vmId,
            'power_on' => true,
        ];
        try {
            $response = $this->client->post('vcenter/vm?action=clone', [
                'headers' => [
                    'vmware-api-session-id' => $this->authToken,
                ],
                'json' => $vmSpec,
            ]);
            if ($response->getStatusCode() === 200) {
                $vmId = $response->getBody()->getContents();
                Log::debug($this->setNetwork($vmId, $projectName));
                if ($this->setNetwork($vmId, $projectName)) {
                    $projectRouter = ProjectRouter::where('project_id', $projectId)->first();
                    $projectRouter->vm_id = $vmId;
                    $projectRouter->save();
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }
    public function getEthernetId($vmId)
    {
        $response = $this->client->get('vcenter/vm/' . str_replace('"', '', $vmId) . '/hardware/ethernet', [
            'headers' => [
                'vmware-api-session-id' => $this->authToken,
            ],
        ]);
        $responseData = json_decode($response->getBody(), true);
        Log::debug($responseData[0]['nic']);
        return $responseData[0]['nic'];
    }
    public function findNetwork($projectName)
    {
        try {
            $pgName = 'Auto_' . $projectName . '|Auto_' . $projectName . 'AP|Auto_' . $projectName . 'EPG';
            $response = $this->client->get('vcenter/network?names=' . $pgName, [
                'headers' => [
                    'vmware-api-session-id' => $this->authToken,
                ],
                // 'json' => [
                //     'filter.names.1' => env('VSPHERE_PROJECT_ROUTER_NETWORK_NAME'),
                // ],
            ]);
            $responseData = json_decode($response->getBody(), true);
            return $responseData[0]['network'];
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }
    public function setNetwork($vmId, $projectName)
    {
        try {
            $response = $this->client->patch('vcenter/vm/' . str_replace('"', '', $vmId) . '/hardware/ethernet/' . $this->getEthernetId($vmId), [
                'headers' => [
                    'vmware-api-session-id' => $this->authToken,
                ],
                'json' => [
                    'backing' => [
                        'type' => 'DISTRIBUTED_PORTGROUP',
                        'network' => $this->findNetwork($projectName),
                    ],
                ],
            ]);
            if ($response->getStatusCode() === 204) {
                return true;
            }
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }
    public function getVmIp($vmId)
    {
        try {
            $response = $this->client->get('vcenter/vm/' . str_replace('"', '', $vmId) . '/guest/networking/interfaces', [
                'headers' => [
                    'vmware-api-session-id' => $this->authToken,
                ],
                'http_errors' => false,
            ]);
            $responseData = json_decode($response->getBody(), true);
            if ($responseData !== [] && $response->getStatusCode() !== 504) {
                return end($responseData[0]['ip']['ip_addresses'])['ip_address'];
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }
}
