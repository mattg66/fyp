<?php

namespace App\Http\Controllers\ACI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Clients\ACIClient;
use App\Models\FabricNode;
use App\Models\InterfaceModel;

class FabricController extends Controller
{

    public function getNodes(Request $request)
    {
        $nodes = [];
        if ($request->has('withoutRack')) {
            if ($request->has('rackId')) {
                $nodes = FabricNode::whereDoesntHave('rack')->orWhere('rack_id', '=', $request->rackId)->get();
            } else {
                $nodes = FabricNode::whereDoesntHave('rack')->get();
            }
        } else {
            $nodes = FabricNode::all();
        }
        return response()->json(
            $nodes
        );
    }
    public function getInterfaces($id)
    {
        $aciClient = new ACIClient();
        $response = $aciClient->getFabricNodeInterfaces($id);
        $fabricNodes = FabricNode::all();
        $newInterface = [];
        foreach ($response as $interface) {
            preg_match('/eth([^\/]+)/', $interface->l1PhysIf->attributes->id, $match);
            foreach ($fabricNodes as $fabricNode) {
                if (($match[1] > 100 && $fabricNode->aci_id == $match[1] && $fabricNode->role === 'fex') || ($match[1] < 100 && $fabricNode->aci_id == $id && $fabricNode->role === 'leaf')) {
                    array_push($newInterface, [
                        'aci_id' => $interface->l1PhysIf->attributes->id,
                        'dn' => $interface->l1PhysIf->attributes->dn,
                        'state' => $interface->l1PhysIf->children[0]->ethpmPhysIf->attributes->operSt,
                        'fabric_node_id' => $fabricNode->id,
                    ]);
                }
            }
        }
        InterfaceModel::insert($newInterface);
        return response()->json(
            $newInterface
        );
    }
}
