<?php

namespace App\Jobs;

use App\Http\Clients\ACIClient;
use App\Http\Clients\IOSXEClient;
use App\Http\Clients\SSHClient;
use App\Http\Clients\vSphereClient;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TSProvision implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $projectId;

    public $uniqueFor = 30;

    function firstUsableIP($network, $subnetMask, $key)
    {
        $ip = ip2long($network); // convert network address to integer
        $mask = ip2long($subnetMask); // convert subnet mask to integer
        $networkID = $ip & $mask; // calculate network ID
        $usableIP = $networkID + 1 + $key; // calculate first usable IP address
        return long2ip($usableIP); // convert back to dotted decimal notation
    }

    public function __construct($projectId)
    {
        $this->projectId = $projectId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $project = Project::with('racks.terminalServer', 'vlan')->find($this->projectId);
        foreach ($project->racks as $key => $rack) {
            if ($rack->terminalServer !== null) {
                $iosXE = new IOSXEClient($rack->terminalServer->ip, $rack->terminalServer->username, $rack->terminalServer->password);
                if ($iosXE->connectionTest()) {
                    if ($iosXE->setSubInterface($this->firstUsableIP($project->network, $project->subnet_mask, $key), $project->subnet_mask, $project->vlan->vlan_id, $rack->terminalServer->uplink_port)) {
                        $iosXE->save($rack->terminalServer->username, $rack->terminalServer->password);
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
