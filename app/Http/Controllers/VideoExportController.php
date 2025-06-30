<?php

namespace App\Http\Controllers;

use App\Models\VideoProject;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Filters\Video\VideoFilters;
use FFMpeg\Filters\Audio\AudioFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class VideoExportController extends Controller
{
    public function export(Request $request, VideoProject $project)
    {
        // Validate that project has at least 4 videos
        if (!$project->hasEnoughVideos()) {
            return response()->json([
                'success' => false,
                'message' => 'Project must have at least 4 videos to export.',
            ], 400);
        }

        // Update project status to processing
        $project->update(['status' => 'processing']);

        try {
            $outputPath = $this->mergeVideos($project, $request->input('text_overlay', ''));

            $project->update([
                'status' => 'completed',
                'output_path' => $outputPath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Video exported successfully!',
                'download_url' => asset('storage/' . str_replace('public/', '', $outputPath)),
            ]);
        } catch (\Exception $e) {
            Log::error('Video export failed: ' . $e->getMessage());

            $project->update(['status' => 'failed']);

            return response()->json([
                'success' => false,
                'message' => 'Video export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function mergeVideos(VideoProject $project, string $textOverlay = ''): string
    {
        $ffmpegPath = config('ffmpeg.ffmpeg.binaries', 'D:/laragon/bin/ffmpeg/ffmpeg.exe');
        $ffmpeg = FFMpeg::create([
            'ffmpeg.binaries' => $ffmpegPath,
            'ffprobe.binaries' => config('ffmpeg.ffprobe.binaries', '/usr/bin/ffprobe'),
        ]);

        $videos = $project->videos()->orderBy('order')->get();
        $outputFilename = 'merged_' . $project->id . '_' . time() . '.mp4';
        $outputPath = 'exports/' . $outputFilename;
        $fullOutputPath = storage_path('app/private/' . $outputPath);

        // Create exports directory if it doesn't exist
        if (!file_exists(storage_path('app/private/exports'))) {
            mkdir(storage_path('app/private/exports'), 0777, true);
        }

        // Build FFmpeg command for concatenation with trimming
        $inputFiles = [];
        $filterComplex = [];
        $trimmedVideoInputs = [];
        $trimmedAudioInputs = [];

        foreach ($videos as $index => $video) {
            $inputFiles[] = '-i "' . $video->full_path . '"';

            // Apply trimming if start_time or end_time is set
            $startTime = $video->start_time ?? 0;
            $endTime = $video->end_time ?? null;

            if ($startTime > 0 || $endTime !== null) {
                // Calculate duration for trim
                $duration = $endTime !== null ? ($endTime - $startTime) : null;

                // Trim video
                $trimmedVideoInputs[] = "[{$index}:v]trim=start={$startTime}" .
                    ($duration !== null ? ":duration={$duration}" : "") .
                    ",setpts=PTS-STARTPTS[v{$index}]";

                // Trim audio
                $trimmedAudioInputs[] = "[{$index}:a]atrim=start={$startTime}" .
                    ($duration !== null ? ":duration={$duration}" : "") .
                    ",asetpts=PTS-STARTPTS[a{$index}]";
            } else {
                // No trimming needed
                $trimmedVideoInputs[] = "[{$index}:v]setpts=PTS-STARTPTS[v{$index}]";
                $trimmedAudioInputs[] = "[{$index}:a]asetpts=PTS-STARTPTS[a{$index}]";
            }
        }

        // Create filter complex for video and audio concatenation
        $videoInputs = array_map(function ($index) {
            return "[v{$index}]";
        }, range(0, count($videos) - 1));
        $audioInputs = array_map(function ($index) {
            return "[a{$index}]";
        }, range(0, count($videos) - 1));

        $filterComplex[] = implode('', $videoInputs) . 'concat=n=' . count($videos) . ':v=1:a=0[outv]';
        $filterComplex[] = implode('', $audioInputs) . 'concat=n=' . count($videos) . ':v=0:a=1[outa]';

        // Combine all filter complex parts
        $allFilters = array_merge($trimmedVideoInputs, $trimmedAudioInputs, $filterComplex);

        // Add text overlay if provided
        if (!empty($textOverlay)) {
            $allFilters[] = '[outv]drawtext=text=\'' . addslashes($textOverlay) . '\':fontcolor=white:fontsize=24:x=(w-text_w)/2:y=h-th-10[outv]';
        }

        $filterComplexStr = implode(';', $allFilters);

        // Build the complete FFmpeg command
        $command = '"' . $ffmpegPath . '" ' . implode(' ', $inputFiles) .
            ' -filter_complex "' . $filterComplexStr . '"' .
            ' -map "[outv]" -map "[outa]"' .
            ' -c:v libx264 -c:a aac' .
            ' -preset medium -crf 23' .
            ' "' . $fullOutputPath . '"';

        Log::info('FFmpeg command: ' . $command);

        // Execute the command
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        Log::info('FFmpeg output: ' . print_r($output, true));

        if ($returnCode !== 0) {
            throw new \Exception('FFmpeg command failed: ' . implode("\n", $output));
        }

        return $outputPath;
    }

    public function download(VideoProject $project)
    {
        if (!$project->isComplete() || !$project->output_path) {
            return response()->json([
                'success' => false,
                'message' => 'Video not ready for download.',
            ], 400);
        }
        $filePath = storage_path('app/private/' . $project->output_path);

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Video file not found.',
            ], 404);
        }

        return response()->download($filePath);
    }

    public function status(VideoProject $project)
    {
        return response()->json([
            'status' => $project->status,
            'has_enough_videos' => $project->hasEnoughVideos(),
            'videos_count' => $project->videos_count,
        ]);
    }
}
