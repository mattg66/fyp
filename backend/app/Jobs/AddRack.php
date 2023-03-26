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

class AddRack implements ShouldQueue
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
        if ($aciClient->deployToNode($this->rackId, $project)) {
            TSProvision::dispatch($this->projectId);
        }
    }
}
