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
    public function provisionCSR($project, $routerIp)
    {
        define('NET_SSH2_LOGGING', 2);

        $ssh = new SSH2($routerIp);
        try {
            if (!$ssh->login(env('VSPHERE_CSRV_USERNAME'), env('VSPHERE_CSRV_SECRET'))) {
                throw new APIClientException('Unable to connect to CSR');
            }
            $ssh->enablePTY();
            $ssh->write('config t\n');
            $ssh->write('hostname testttt\n');
            Log::debug($ssh->exec('config t'));
            Log::debug($ssh->write('hostname hellooo'));

            // $ssh->exec('term shell; conf t; hostname hello;', function($str) {
            //     Log::debug($str);
                
            // });
            $project->projectRouter->status = 'provisioned';
            $project->projectRouter->mgmt_ip = $routerIp;
            $project->projectRouter->save();
            return true;
        } catch (\Exception $e) {
            Log::debug($ssh->getLog());
            throw new APIClientException('Unable to connect to CSR');
        }
    }
}
