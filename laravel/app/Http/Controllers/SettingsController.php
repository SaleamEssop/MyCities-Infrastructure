<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SettingsController extends Controller
{
    /**
     * Display general application settings
     */
    public function index()
    {
        $settings = Settings::first() ?? new Settings();
        return view('admin.settings', compact('settings'));
    }

    /**
     * Update general application settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'demo_mode' => 'required|boolean',
        ]);

        $settings = Settings::first();
        
        if (!$settings) {
            $settings = new Settings();
        }

        $settings->demo_mode = $request->demo_mode;
        $settings->save();

        Session::flash('alert-class', 'alert-success');
        Session::flash('alert-message', 'Settings updated successfully');

        return redirect()->route('settings.index');
    }
}
