<?php

namespace App\Http\Clients;

use GuzzleHttp\Client;
use App\Exceptions\APIClientException;
use App\Jobs\SyncACI;
use App\Models\FabricNode;
use App\Models\InterfaceModel;
use App\Models\VlanPool;
use Illuminate\Support\Facades\DB;

class ACIClient
{
    protected $client;
    protected $authToken;

    public function __construct($token = null)
    {
        $this->client = new Client([
            'base_uri' => 'https://' . env('APIC_IPADDR') .  '/api/',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'verify' => false,
        ]);
        if ($token === null) {
            if (env('APIC_IPADDR') == null || env('APIC_USERNAME') == null || env('APIC_PASSWORD') == null) {
                throw new APIClientException('APIC credentials not set');
            }
            $this->connect();
        } else {
            $this->authToken = $token;
        }
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
                SyncACI::dispatch($data->imdata[0]->aaaLogin->attributes->token);
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
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
    public function syncFabricNodes()
    {
        DB::beginTransaction();
        try {
            $response = $this->client->get('node/mo/topology/pod-' . env('ACI_POD') . '.json?query-target=children&target-subtree-class=fabricNode&query-target-filter=and(not(wcard(fabricNode.dn,"__ui_")),and(ne(fabricNode.role,"controller")))', [
                'headers' => [
                    'Cookie' => 'APIC-cookie=' . $this->authToken,
                ],
            ]);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody());
                $nodes = [];
                $dn = [];
                foreach ($data->imdata as $node) {
                    if ($node->fabricNode->attributes->role == 'leaf') {
                        array_push($dn, $node->fabricNode->attributes->dn);
                        $childFex = [];
                        $checkFex = $this->client->get('node/class/topology/pod-1/node-' . $node->fabricNode->attributes->id . '/eqptExtCh.json', [
                            'headers' => [
                                'Cookie' => 'APIC-cookie=' . $this->authToken,
                            ],
                        ]);
                        FabricNode::updateOrCreate(['dn' => $node->fabricNode->attributes->dn], [
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
                                'description' => $node->fabricNode->attributes->name . ' - FEX' . $fex->eqptExtCh->attributes->id,
                                'serial' => $fex->eqptExtCh->attributes->ser,
                            ];
                            array_push($dn, $fex->eqptExtCh->attributes->dn);
                            FabricNode::updateOrCreate(['dn' => $fex->eqptExtCh->attributes->dn], $childFexData);
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
                FabricNode::whereNotIn('dn', $dn)->delete();
                DB::commit();
                return $nodes;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            DB::rollback();
            throw new APIClientException($e->getMessage());
        }
    }
    public function syncFabricInterfaces()
    {
        DB::beginTransaction();
        try {
            $fabricNodes = FabricNode::all();
            foreach ($fabricNodes as $fabricNode) {
                if ($fabricNode->role === 'leaf') {
                    $response = $this->client->get('node/class/topology/pod-' . env('ACI_POD') . '/node-' . $fabricNode->aci_id . '/l1PhysIf.json?rsp-subtree=children&rsp-subtree-class=ethpmPhysIf&rsp-subtree-include=required&order-by=l1PhysIf.id|asc', [
                        'headers' => [
                            'Cookie' => 'APIC-cookie=' . $this->authToken,
                        ],
                    ]);
                    if ($response->getStatusCode() == 200) {
                        $data = json_decode($response->getBody());
                        foreach ($data->imdata as $interface) {
                            preg_match('/eth([^\/]+)/', $interface->l1PhysIf->attributes->id, $match);
                            if ($match[1] !== $fabricNode->aci_id && $match[1] <= 100) {
                                InterfaceModel::updateOrCreate(['dn' => $interface->l1PhysIf->attributes->dn], [
                                    'aci_id' => $interface->l1PhysIf->attributes->id,
                                    'dn' => $interface->l1PhysIf->attributes->dn,
                                    'state' => $interface->l1PhysIf->children[0]->ethpmPhysIf->attributes->operSt,
                                    'fabric_node_id' => $fabricNode->id,
                                ]);
                            } else {
                                foreach ($fabricNodes as $fabricNode2) {
                                    if ($fabricNode2->role === 'fex' && $fabricNode2->aci_id == $match[1]) {
                                        InterfaceModel::updateOrCreate(['dn' => $interface->l1PhysIf->attributes->dn], [
                                            'aci_id' => $interface->l1PhysIf->attributes->id,
                                            'dn' => $interface->l1PhysIf->attributes->dn,
                                            'state' => $interface->l1PhysIf->children[0]->ethpmPhysIf->attributes->operSt,
                                            'fabric_node_id' => $fabricNode2->id,
                                        ]);
                                    }
                                }
                            }
                        }
                    } else {
                        DB::rollBack();
                        return false;
                    }
                }
            }
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new APIClientException($e->getMessage());
        }
    }
    public function getFabricNodeInterfaces($id)
    {
        try {
            $response = $this->client->get('node/class/topology/pod-' . env('ACI_POD') . '/node-' . $id . '/l1PhysIf.json?rsp-subtree=children&rsp-subtree-class=ethpmPhysIf&rsp-subtree-include=required&order-by=l1PhysIf.id|asc', [
                'headers' => [
                    'Cookie' => 'APIC-cookie=' . $this->authToken,
                ],
            ]);
            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody());
                return $data->imdata;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }
    public function getVlanPools()
    {
        try {
            $response = $this->client->get('node/mo/uni/infra.json?query-target=subtree&target-subtree-class=fvnsVlanInstP&query-target-filter=not(wcard(fvnsVlanInstP.dn,%22__ui_%22))&target-subtree-class=fvnsEncapBlk&query-target=subtree&rsp-subtree=full&rsp-subtree-class=tagAliasInst', [
                'headers' => [
                    'Cookie' => 'APIC-cookie=' . $this->authToken,
                ],
            ]);
            $dataArr = [];
            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody());
                foreach ($data->imdata as $item) {
                    // check if the item has the fvnsVlanInstP key
                    if (isset($item->fvnsVlanInstP)) {
                        // get the dn value of the fvnsVlanInstP item
                        $vlanDn = $item->fvnsVlanInstP->attributes->dn;
                        $tempData = [];
                        // iterate through the data array again to find the matching fvnsEncapBlk item
                        foreach ($data->imdata as $childItem) {
                            // check if the item has the fvnsEncapBlk key
                            if (isset($childItem->fvnsEncapBlk)) {
                                // get the dn value of the fvnsEncapBlk item
                                $encapDn = $childItem->fvnsEncapBlk->attributes->dn;
                                // check if the fvnsEncapBlk dn contains the fvnsVlanInstP dn
                                if (strpos($encapDn, $vlanDn) !== false) {
                                    // match found, do something with the data
                                    array_push($tempData, $childItem->fvnsEncapBlk);
                                }
                            }
                        }
                        $temp = $item->fvnsVlanInstP;
                        $temp->children = $tempData;
                        array_push($dataArr, $temp);
                    }
                }
                return $dataArr;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
            
    }
    public function syncVlanPools()
    {
        DB::beginTransaction();
        try {
            $vlanPools = $this->getVlanPools();
            $dn = [];
            foreach ($vlanPools as $vlanPool) {
                if ($vlanPool->children != null) {
                    foreach ($vlanPool->children as $children) {
                        array_push($dn, $children->attributes->dn);
                        $allocMode = '';
                        if($children->attributes->allocMode === 'inherit') {
                            $allocMode = $vlanPool->attributes->allocMode;
                        } else {
                            $allocMode = $children->attributes->allocMode;
                        }
                        VlanPool::updateOrCreate(['dn' => $children->attributes->dn], [
                            'name' => $vlanPool->attributes->name,
                            'dn' => $children->attributes->dn,
                            'start' => substr($children->attributes->from, 5),
                            'end' => substr($children->attributes->to, 5),
                            'parent_dn' => $vlanPool->attributes->dn,
                            'alloc_mode' => $allocMode
                        ]);
                    }
                }
            }
            VlanPool::whereNotIn('dn', $dn)->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new APIClientException($e->getMessage());
        }
    }
}
