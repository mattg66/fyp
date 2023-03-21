<?php

namespace App\Http\Clients;

use App\Exceptions\APIClientException;
use App\Models\Project;
use Illuminate\Support\Facades\Log;
use phpseclib3\Net\SSH2;

class SSHClient
{
    public function __construct()
    {
        if (env('VSPHERE_CSRV_SECRET') == null || env('VSPHERE_CSRV_USERNAME') == null) {
            throw new APIClientException('SSH credentials not set');
        }
    }
    function getHighestUsableIPAddress($networkAddress, $subnetMask) {
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
    public function provisionCSR($project, $routerIp)
    {
        Log::debug($project);
        $ssh = new SSH2($routerIp);
        if (!$ssh->login(env('VSPHERE_CSRV_USERNAME'), env('VSPHERE_CSRV_SECRET'))) {
            throw new APIClientException('Unable to connect to CSR');
        }
        $ssh->exec('configure terminal');
        $ssh->exec('hostname ' . $project->name . '-CSR');
        $ssh->exec('interface GigabitEthernet 1');
        $ssh->exec('ip address ' . $this->getHighestUsableIPAddress($project->network, $project->subnet_mask) . ' ' . $project->projectRouter->subnet_mask);
        $ssh->exec('no shutdown');
        $ssh->exec('exit');
        $ssh->exec('interface GigabitEthernet 2');
        $ssh->exec('ip address ' . $project->projectRouter->ip . ' ' . $project->projectRouter->subnet_mask);
        $ssh->exec('no shutdown');
        $ssh->exec('ip route 0.0.0.0 0.0.0.0 ' . $project->projectRouter->gateway);
        $ssh->exec('do wr');
        $ssh->exec('end');
        $project->projectRouter->status = 'provisioned';
        $project->projectRouter->mgmt_ip = $routerIp;
        $project->projectRouter->save();
        return true;
    }
}