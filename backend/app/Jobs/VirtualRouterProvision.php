<?php

namespace App\Jobs;

use App\Http\Clients\ACIClient;
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

class VirtualRouterProvision implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $projectId;

    public $uniqueFor = 30;


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
        $vmWare = new vSphereClient();
        $project = Project::with('projectRouter')->find($this->projectId);
        for ($i = 0; $i < 5; $i++) {
            $routerIp = $vmWare->getVmIp($project->projectRouter->vm_id);
            if ($routerIp !== false && $routerIp != '0.0.0.0') {
                $ssh = new SSHClient();
                $ssh->provisionCSR($project, $routerIp);
                return true;
            } else {
                sleep(10);
            }
        }
        return false;
    }
}
