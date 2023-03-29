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

class CreateProject implements ShouldQueue
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
        $project = Project::find($this->projectId);

        if ($aciClient->createTenant($this->projectName)) {
            if ($aciClient->createBD($this->projectName)) {
                if ($aciClient->createAP($this->projectName)) {
                    if ($aciClient->createEPG($this->projectName)) {
                        if ($aciClient->associatePhysDom($this->projectName)) {
                            if ($aciClient->deployToNodes($this->projectId)) {
                                $project->status = 'VMware';
                                $project->save();
                                if ($vmWare->deployProjectRouter($this->projectName, $this->projectId)) {
                                    VirtualRouterProvision::dispatch($this->projectId)->delay(Carbon::now()->addSeconds(140));
                                    TSProvision::dispatch($this->projectId);
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
        }
        $project->status = 'Error';
        $project->save();
        return false;
    }
}
