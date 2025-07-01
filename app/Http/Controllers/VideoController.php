<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\VideoProject;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Coordinate\Dimension;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    public function store(Request $request, VideoProject $project)
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,avi,mov,wmv,flv,webm|max:102400', // 100MB max
        ]);

        $file = $request->file('video');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('videos/' . $project->id, $filename);

        // Get video metadata using FFmpeg
        $ffmpeg = FFMpeg::create([
            'ffmpeg.binaries' => config('ffmpeg.ffmpeg.binaries', '/usr/bin/ffmpeg'),
            'ffprobe.binaries' => config('ffmpeg.ffprobe.binaries', '/usr/bin/ffprobe'),
        ]);

        $video = $ffmpeg->open(storage_path('app/private/' . $path));
        $stream = $video->getStreams()->first();

        $duration = $stream->get('duration');
        $width = $stream->get('width');
        $height = $stream->get('height');

        $videoModel = $project->videos()->create([
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'duration' => (int) $duration,
            'width' => (int) $width,
            'height' => (int) $height,
            'order' => $project->videos()->count(),
        ]);

        $videoModel->append('public_url');
        $videoModel->append('trimmed_duration');
        $videoModel->append('formatted_trimmed_duration');

        return response()->json([
            'success' => true,
            'video' => $videoModel->load('project'),
        ]);
    }

    public function update(Request $request, Video $video)
    {
        $validated = $request->validate([
            'start_time' => 'nullable|integer|min:0',
            'end_time' => 'nullable|integer|min:0|gt:start_time',
            'order' => 'nullable|integer|min:0',
        ]);

        $video->update($validated);

        $updatedVideo = $video->fresh();
        $updatedVideo->append('public_url');
        $updatedVideo->append('trimmed_duration');
        $updatedVideo->append('formatted_trimmed_duration');

        return response()->json([
            'success' => true,
            'video' => $updatedVideo,
        ]);
    }

    public function destroy(Video $video)
    {
        // Delete the file from storage
        if (Storage::exists($video->file_path)) {
            Storage::delete($video->file_path);
        }

        $video->delete();

        return response()->json(['success' => true]);
    }

    public function reorder(Request $request, VideoProject $project)
    {
        $validated = $request->validate([
            'videos' => 'required|array',
            'videos.*.id' => 'required|exists:videos,id',
            'videos.*.order' => 'required|integer|min:0',
        ]);

        foreach ($validated['videos'] as $videoData) {
            $project->videos()
                ->where('id', $videoData['id'])
                ->update(['order' => $videoData['order']]);
        }

        return response()->json(['success' => true]);
    }

    public function stream(Video $video)
    {
        $path = storage_path('app/private/' . $video->file_path);
        if (!file_exists($path)) {
            abort(404);
        }
        return response()->file($path);
    }
}
