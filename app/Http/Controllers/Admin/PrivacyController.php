<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PrivacyPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrivacyController extends Controller
{
    public function index()
    {
        return view('admin.privacy', [
            'p' => PrivacyPolicy::get(),
            'requests' => DB::table('activity_log')->where('event', 'privacy.request')->latest('id')->limit(20)->get(),
        ]);
    }

    public function save(Request $request)
    {
        $d = $request->validate([
            'eyebrow' => 'nullable|string|max:60', 'title' => 'required|string|max:120', 'effective_date' => 'nullable|string|max:40',
            'summary' => 'nullable|string|max:20000', 'contact_team' => 'nullable|string|max:120', 'contact_email' => 'nullable|email|max:190',
            'response_promise' => 'nullable|string|max:500', 'collection' => 'nullable|string|max:30000', 'rights' => 'nullable|string|max:30000',
            'retention_days' => 'required|integer|min:0|max:3650',
        ]);
        PrivacyPolicy::save($d + ['enabled' => $request->boolean('enabled'), 'request_form' => $request->boolean('request_form')]);
        \App\Support\Audit::log('privacy.updated', null, ['retention_days' => $d['retention_days']]);

        return back()->with('success', 'Privacy settings saved.');
    }
}
