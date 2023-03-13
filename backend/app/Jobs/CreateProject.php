<?php

namespace App\Jobs;

use App\Http\Clients\ACIClient;
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

    public $uniqueFor = 30;


    public function __construct($projectName)
    {
        $this->projectName = $projectName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $aciClient = new ACIClient();
        if ($aciClient->createTenant($this->projectName)) {
            if ($aciClient->createBD($this->projectName)) {
                if ($aciClient->createAP($this->projectName)) {
                    if ($aciClient->createEPG($this->projectName)) {
                        if ($aciClient->associatePhysDom($this->projectName)){
                            return true;
                        }
                    }
                }
            }
        }
    }
}
