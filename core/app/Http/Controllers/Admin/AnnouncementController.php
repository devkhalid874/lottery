<?php

namespace App\Http\Controllers\Admin;

use App\Models\Announcement;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pageTitle = "Manage Announcements";
        $announcements = Announcement::latest()->get();
        return view('admin.announcement.index', compact('pageTitle', 'announcements'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'media_type'  => 'nullable|in:image,video',
            'media_path'  => 'nullable|file|mimes:jpg,jpeg,png,webp,mp4,mov,avi|max:20480',
            'is_active'   => 'nullable|boolean',
        ]);


        $file = $request->file('media_path');
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();

        $destination = base_path('../assets/images/announcement');

        $file->move($destination, $filename);

        Announcement::create([
            'title'       => $request->title,
            'description' => $request->description,
            'media_type'  => $request->media_type,
            'media_path'  => $filename,
            'is_active'   => $request->boolean('is_active'),
        ]);

        $notify[] = ['success', 'Announcement created successfully'];
        return back()->withNotify($notify);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'id'          => 'required|exists:announcements,id',
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'media_type'  => 'required|in:image,video',
            'media_path'  => 'nullable|file|mimes:jpg,jpeg,png,webp,mp4,mov,avi|max:20480',
            'is_active'   => 'nullable|boolean',
        ]);

        $announcement = Announcement::findOrFail($request->id);

        if ($request->hasFile('media_path')) {
    
            $oldPath = base_path('../assets/images/announcement/' . $announcement->media_path);
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }

            $file = $request->file('media_path');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(base_path('../assets/images/announcement'), $filename);

            $announcement->media_path = $filename;
        }

        $announcement->title = $request->title;
        $announcement->description = $request->description;
        $announcement->media_type = $request->media_type;
        $announcement->is_active = $request->boolean('is_active');
        $announcement->save();

        $notify[] = ['success', 'Announcement updated successfully'];
        return back()->withNotify($notify);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id);

        $filePath = base_path('assets/images/announcement/' . $announcement->media_path);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $announcement->delete();

        $notify[] = ['success', 'Announcement deleted successfully'];
        return back()->withNotify($notify);
    }
}
