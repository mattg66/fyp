<?php

namespace App\Jobs;

use App\Http\Clients\ACIClient;
use App\Http\Clients\vSphereClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VirtualRouterProvision implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $virtualRouterId;
    public $projectId;

    public $uniqueFor = 30;


    public function __construct($virtualRouterId, $projectId)
    {
        $this->virtualRouterId = $virtualRouterId;
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
        for ($i = 0; $i < 5; $i++) {
            if ($vmWare->getVmIp($this->virtualRouterId !== false)) {
                
            } else {
                sleep(10);
            }
        }
        return false;
    }
}
