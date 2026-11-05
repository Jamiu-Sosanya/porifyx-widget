<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CookieConsent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsentController extends Controller
{
    public function edit()
    {
        return view('admin.consent', ['consent' => CookieConsent::get(), 'layouts' => CookieConsent::LAYOUTS]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'layout'          => ['required', Rule::in(array_keys(CookieConsent::LAYOUTS))],
            'title'           => 'required|string|max:80',
            'message'         => 'required|string|max:600',
            'accept_label'    => 'required|string|max:30',
            'reject_label'    => 'required|string|max:30',
            'customize_label' => 'required|string|max:30',
        ]);

        foreach (['enabled', 'show_reject', 'show_customize', 'analytics', 'marketing', 'block_page'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        // "Ask everyone again" bumps the version so stored choices no longer match.
        $current = CookieConsent::get();
        $data['version'] = $current['version'] + ($request->boolean('reset') ? 1 : 0);

        CookieConsent::save($data);

        return back()->with('success', $request->boolean('reset')
            ? 'Cookie banner saved. Every visitor will be asked again.'
            : 'Cookie banner saved.');
    }
}
