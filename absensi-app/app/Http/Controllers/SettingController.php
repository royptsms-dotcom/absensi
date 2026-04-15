<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = $this->getSettings();
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $settings = [
            'check_in_limit' => $request->check_in_limit,
            'check_out_limit' => $request->check_out_limit,
        ];

        file_put_contents(storage_path('app/settings.json'), json_encode($settings));

        return redirect()->back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    private function getSettings()
    {
        $path = storage_path('app/settings.json');
        if (!file_exists($path)) {
            return ['check_in_limit' => '08:00', 'check_out_limit' => '17:00'];
        }
        return json_decode(file_get_contents($path), true);
    }

}
