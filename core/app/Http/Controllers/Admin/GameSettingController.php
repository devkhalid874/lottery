<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameSetting;
use Illuminate\Http\Request;

class GameSettingController extends Controller
{
    public function index()
    {
        $pageTitle = "Manage Game Settings";
        $times     = GameSetting::orderBy('id')->get();
        return view('admin.game_setting.index', compact('pageTitle', 'times'));
    }

    public function store(Request $request)
    {
        $this->validation($request);
        $time = new GameSetting();
        $this->submitData($time, $request);

        $notify[] = ['success', 'Time schedule added successfully'];
        return back()->withNotify($notify);
    }

    public function update(Request $request, $id)
    {
        $this->validation($request);
        $time = GameSetting::findOrFail($id);
        $this->submitData($time, $request);

        $notify[] = ['success', 'Time schedule added successfully'];
        return back()->withNotify($notify);
    }

    public function submitData($time, $request)
    {
        $time->name = $request->name;
        $time->time = $request->time;
        $time->save();
    }

    public function validation($request)
    {
        $request->validate([
            'name' => 'required',
            'time' => 'required|numeric|min:0',
        ]);
    }

    public function status($id)
    {
        return GameSetting::changeStatus($id);
    }
}
