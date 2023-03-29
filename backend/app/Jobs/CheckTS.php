<?php

namespace App\Jobs;

use App\Http\Clients\ACIClient;
use App\Http\Clients\IOSXEClient;
use App\Http\Clients\SSHClient;
use App\Http\Clients\vSphereClient;
use App\Models\Project;
use App\Models\TerminalServer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckTS implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    public $uniqueFor = 30;


    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $tss = TerminalServer::all();
        foreach ($tss as $ts) {
            $iosXE = new IOSXEClient($ts->ip, $ts->username, $ts->password);
            if ($iosXE->connectionTest()) {
                $ts->status = 'Connected';
                $ts->version = $iosXE->getVersion();
                $ts->serial_number = $iosXE->getSN();
                $ts->save();
            }
        }
        return true;
    }
}
