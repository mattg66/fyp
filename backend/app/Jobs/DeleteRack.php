<?php

namespace App\Jobs;

use App\Http\Clients\ACIClient;
use App\Http\Clients\IOSXEClient;
use App\Http\Clients\vSphereClient;
use App\Models\Project;
use App\Models\Rack;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteRack implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $projectId;
    public $rackId;

    public $uniqueFor = 30;


    public function __construct($projectId, $rackId)
    {
        $this->projectId = $projectId;
        $this->rackId = $rackId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $aciClient = new ACIClient();
        $project = Project::with(['vlan', 'racks'])->find($this->projectId);
        $rack = Rack::with('terminalServer')->find($this->rackId);
        if ($aciClient->removeFromNode($this->rackId, $project) && $aciClient->deleteIntProfRack($this->projectId, $rack)){
            if ($rack->terminalServer !== null) {
                $iosXEClient = new IOSXEClient($rack->terminalServer->ip, $rack->terminalServer->username, $rack->terminalServer->password);
                $iosXEClient->deleteSubIf($project->vlan->vlan_id, $rack->terminalServer->uplink_port);
                $iosXEClient->save();
            }
            $rack->project_id = null;
            $rack->save();
        }
    }
}
