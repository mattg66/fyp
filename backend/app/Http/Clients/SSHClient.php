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
        if (env('PROJECT_ROUTER_SECRET') == null || env('PROJECT_ROUTER_USERNAME') == null) {
            throw new APIClientException('SSH credentials not set');
        }
    }
    
    public function provisionCSR($project, $routerIp)
    {
        define('NET_SSH2_LOGGING', 2);

        $ssh = new SSH2($routerIp);
        try {
            if (!$ssh->login(env('PROJECT_ROUTER_USERNAME'), env('PROJECT_ROUTER_SECRET'))) {
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
