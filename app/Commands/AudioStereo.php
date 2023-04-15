<?php

namespace App\Commands;

use Illuminate\Support\Facades\Process;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Exception\RuntimeException;

class AudioStereo extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'audio:stereo
                            {file : The video file to process}
                            {--silent : Hide the progress output}';
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
        // get the input file
        $file = $this->argument('file');

        if (!is_readable($file)) {
            throw new RuntimeException("There was an error accessing the provided file, confirm it exists and can be read.");
        }

        // get the audio tracks for the file
        $probe_command = 'ffprobe -i "' . $file . '" -show_streams -select_streams a -print_format json -v quiet';

        $result = Process::run($probe_command)->throw(function ($result, $exception) {
            throw new RuntimeException("There was an error scanning the provided file.");
        });

        $audio_streams = json_decode($result->output());

        if (empty($audio_streams->streams)) {
            throw new RuntimeException("The provided file does not contain any audio tracks.");
        }

        if ($audio_streams->streams[0]->channels < 4) {
            throw new RuntimeException('The first audio track is already in stereo.');
        }

        // build the initial conversion command
        // script 05 - https://forums.plex.tv/t/323820
        $convert_command = 'ffmpeg -y -v quiet -i "' . $file . '" -map 0:v -c:v copy -map 0:a:0? -c:a:0 copy -map 0:a:0? -c:a:1 aac -ac 2 -filter:a:1 "acompressor=ratio=4,loudnorm" -ar:a:1 48000 -b:a:1 192k -metadata:s:a:1 title="Eng 2.0 Stereo DRC" -metadata:s:a:1 language=eng';

        $total_tracks = count($audio_streams->streams) - 1;

        // extend the command to move all the existing audio tracks to accomodate the new one
        if ($total_tracks > 0) {
            for ($i = 1; $i <= $total_tracks; $i++) {
                $convert_command .= " -map 0:a:" . $i . "? -c:a:" . $i + 1 . " copy";
            }
        }

        // add the subtitles and define the output path
        $convert_command .= ' -map 0:s? -c:s copy "new-' . $file . '"';

        // stream the progress 
        if (!$this->option('silent')) {
            $convert_command .= ' -stats';
        }

        // convert the audio track
        Process::forever()->tty()->run($convert_command, function (string $type, string $output) {
            $this->info($output);
        })->throw(function ($result, $exception) {
            throw new RuntimeException("There was an error converting the audio track for the provided file.");
        });
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
