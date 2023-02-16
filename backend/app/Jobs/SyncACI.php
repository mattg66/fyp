<?php

namespace App\Jobs;

use App\Http\Clients\ACIClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncACI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $authToken;

    public $uniqueFor = 30;


    public function __construct($token)
    {
        $this->authToken = $token;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $aciClient = new ACIClient($this->authToken);
        $aciClient->syncFabricNodes();
        $aciClient->syncFabricInterfaces();
    }
}
