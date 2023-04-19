<?php

namespace App\Jobs;

use App\Http\Clients\ACIClient;
use App\Http\Clients\IOSXEClient;
use App\Http\Clients\vSphereClient;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteProject implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $projectName;
    public $projectId;

    public $uniqueFor = 30;


    public function __construct($projectName, $projectId)
    {
        $this->projectName = $projectName;
        $this->projectId = $projectId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $aciClient = new ACIClient();
        $vmWare = new vSphereClient();
        $project = Project::with('projectRouter', 'racks.terminalServer', 'vlan')->where('id', $this->projectId)->withTrashed()->first();
        if ($vmWare->powerOffVm($project->projectRouter->vm_id)) {
            if ($vmWare->deleteVm($project->projectRouter->vm_id)) {
                if ($aciClient->deleteTenant($this->projectName)) {
                    if ($aciClient->deleteIntProf($this->projectId)) {
                        foreach ($project->racks as $key => $rack) {
                            if ($rack->terminalServer !== null) {
                                $iosXE = new IOSXEClient($rack->terminalServer->ip, $rack->terminalServer->username, $rack->terminalServer->password);
                                if ($iosXE->connectionTest()) {
                                    if ($iosXE->deleteSubIf($project->vlan->vlan_id, $rack->terminalServer->uplink_port)) {
                                        $iosXE->save($rack->terminalServer->username, $rack->terminalServer->password);
                                    }
                                }
                            }
                        }
                        $project->forceDelete();
                        return true;
                    }
                }
            }
        }
    }
}
