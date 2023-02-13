<?php

namespace App\Http\Clients;

use GuzzleHttp\Client;
use App\Exceptions\APIClientException;
use App\Models\FabricNode;

class ACIClient
{
    protected $client;
    protected $authToken;

    public function __construct()
    {
        if (env('APIC_IPADDR') == null || env('APIC_USERNAME') == null || env('APIC_PASSWORD') == null) {
            throw new APIClientException('APIC credentials not set');
        }
        $this->client = new Client([
            'base_uri' => 'https://' . env('APIC_IPADDR') .  '/api/',
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
            $response = $this->client->post('aaaLogin.json', [
                'json' => [
                    'aaaUser' => [
                        'attributes' => [
                            'name' => env('APIC_USERNAME'),
                            'pwd' => env('APIC_PASSWORD'),
                        ],
                    ],
                ],
            ]);
            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody());
                $this->authToken = $data->imdata[0]->aaaLogin->attributes->token;
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new APIClientException('Unable to connect to APIC');
        }
    }
    public function getApicVersion()
    {
        $response = $this->client->get('class/topSystem.json', [
            'headers' => [
                'Cookie' => 'APIC-Cookie=' . $this->authToken,
            ],
        ]);
        $data = json_decode($response->getBody());
        return $data->imdata[0]->topSystem->attributes->version;
    }
    public function getHealth()
    {
        $response = $this->client->get('node/class/fabricHealthTotal.json', [
            'headers' => [
                'Cookie' => 'APIC-Cookie=' . $this->authToken,
            ],
        ]);
        $data = json_decode($response->getBody());
        return $data->imdata[0]->fabricHealthTotal->attributes->cur;
    }
    public function getFabricHealth()
    {
        try {
            $response = $this->client->get('node/class/fabricNode.json', [
                'headers' => [
                    'Cookie' => 'APIC-Cookie=' . $this->authToken,
                ],
            ]);
            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody());
                return $data->imdata;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
    public function getFabricNodes()
    {
        try {
            $response = $this->client->get('node/mo/topology/pod-1.json?query-target=children&target-subtree-class=fabricNode&query-target-filter=and(not(wcard(fabricNode.dn,"__ui_")),and(ne(fabricNode.role,"controller")))', [
                'headers' => [
                    'Cookie' => 'APIC-cookie=' . $this->authToken,
                ],
            ]);
            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody());
                $nodes = [];
                foreach ($data->imdata as $node) {
                    if ($node->fabricNode->attributes->role == 'leaf') {

                        $childFex = [];
                        $checkFex = $this->client->get('node/class/topology/pod-1/node-' . $node->fabricNode->attributes->id . '/eqptExtCh.json', [
                            'headers' => [
                                'Cookie' => 'APIC-cookie=' . $this->authToken,
                            ],
                        ]);
                        FabricNode::upsert([
                            'dn' => $node->fabricNode->attributes->dn,
                            'aci_id' => $node->fabricNode->attributes->id,
                            'model' => $node->fabricNode->attributes->model,
                            'role' => 'leaf',
                            'description' => $node->fabricNode->attributes->name,
                            'serial' => $node->fabricNode->attributes->serial,
                        ]);
                        $checkFexData = json_decode($checkFex->getBody());
                        foreach ($checkFexData->imdata as $fex) {
                            $childFexData = [
                                'dn' => $fex->eqptExtCh->attributes->dn,
                                'aci_id' => $fex->eqptExtCh->attributes->id,
                                'model' => $fex->eqptExtCh->attributes->model,
                                'role' => 'fex',
                                'description' => $fex->eqptExtCh->attributes->descr,
                                'serial' => $fex->eqptExtCh->attributes->ser,
                            ];
                            FabricNode::upsert($childFexData);
                            array_push($childFex, $childFexData);
                        }
                        if (count($childFex) > 0) {
                            array_push($nodes, [
                                'dn' => $node->fabricNode->attributes->dn,
                                'aci_id' => $node->fabricNode->attributes->id,
                                'model' => $node->fabricNode->attributes->model,
                                'role' => $node->fabricNode->attributes->role,
                                'description' => $node->fabricNode->attributes->name,
                                'serial' => $node->fabricNode->attributes->serial,
                                'childFex' => $childFex,
                            ]);
                        } else {
                            array_push($nodes, [
                                'dn' => $node->fabricNode->attributes->dn,
                                'aci_id' => $node->fabricNode->attributes->id,
                                'model' => $node->fabricNode->attributes->model,
                                'role' => $node->fabricNode->attributes->role,
                                'description' => $node->fabricNode->attributes->name,
                                'serial' => $node->fabricNode->attributes->serial,
                            ]);
                        }
                    }
                }
                return $nodes;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }
}
