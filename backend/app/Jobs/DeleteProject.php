<?php

namespace App\Jobs;

use App\Http\Clients\ACIClient;
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
        $project = Project::with('projectRouter')->where('id', $this->projectId)->withTrashed()->first();
        if ($vmWare->powerOffVm($project->projectRouter->vm_id)) {
            if ($vmWare->deleteVm($project->projectRouter->vm_id)) {
                if ($aciClient->deleteTenant($this->projectName)) {
                    if ($aciClient->deleteIntProf($this->projectId)){
                        $project->forceDelete();
                        return true;
                    }
                }
            }
        }
        
    }
}