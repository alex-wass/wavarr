<?php

namespace App\Commands;

use Illuminate\Support\Facades\Process;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class AudioStereo extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'audio:stereo
                            {file : The video file to process}';
    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a stereo audio track from the first track, and append it to the video container.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
