<?php

namespace App\Http\Clients;

use App\Exceptions\APIClientException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class vSphereClient
{

    protected $client;
    protected $authToken;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://' . env('VSPHERE_IPADDR') . '/',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'verify' => false,
        ]);

        if (!$this->connect()) {
            throw new APIClientException('Unable to connect to VSphere');
        }
    }

    protected function connect()
    {
        try {
            $response = $this->client->post('api/session', [
                'auth' => [
                    env('VSPHERE_USERNAME'),
                    env('VSPHERE_PASSWORD'),
                ],
            ]);
        } catch (RequestException $e) {
            return false;
        }

        $responseData = json_decode($response->getBody(), true);
        $this->authToken = $responseData;
        return true;
    }

    public function getServiceStatus()
    {
        try {
            $response = $this->client->get('api/vcenter/services', [
                'headers' => [
                    'vmware-api-session-id' => $this->authToken,
                ],
            ]);
        } catch (RequestException $e) {
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
        } catch (RequestException $e) {
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
}
