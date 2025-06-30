<?php

namespace App\Http\Controllers;

use App\Models\VideoProject;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VideoProjectController extends Controller
{
    public function index(): Response
    {
        $projects = auth()->user()->videoProjects()
            ->withCount('videos')
            ->latest()
            ->get();

        return Inertia::render('VideoEditor/Projects', [
            'projects' => $projects,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('VideoEditor/CreateProject');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project = auth()->user()->videoProjects()->create($validated);

        return redirect()->route('video-editor.edit', $project)
            ->with('success', 'Project created successfully!');
    }

    public function edit(VideoProject $project): Response
    {
        $project->load(['videos' => function ($query) {
            $query->orderBy('order');
        }]);
        $project->videos->each->append('public_url');

        return Inertia::render('VideoEditor/EditProject', [
            'project' => $project,
        ]);
    }

    public function update(Request $request, VideoProject $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'settings' => 'nullable|array',
        ]);

        $project->update($validated);

        return back()->with('success', 'Project updated successfully!');
    }

    public function destroy(VideoProject $project)
    {
        $project->delete();

        return redirect()->route('video-editor.projects')
            ->with('success', 'Project deleted successfully!');
    }
}
