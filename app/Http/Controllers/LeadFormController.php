<?php

namespace App\Http\Controllers;

use App\Models\Widget;
use App\Support\LeadForm;
use Illuminate\Http\Request;

class LeadFormController extends Controller
{
    public function show(Widget $widget)
    {
        $this->authorize('update', $widget);

        return view('widgets.lead-form', ['widget' => $widget, 'form' => LeadForm::settings($widget)]);
    }

    public function save(Request $request, Widget $widget)
    {
        $this->authorize('update', $widget);
        $d = $request->validate([
            'mode'              => 'required|in:' . implode(',', array_keys(LeadForm::MODES)),
            'title'             => 'nullable|string|max:120',
            'intro'             => 'nullable|string|max:300',
            'button'            => 'nullable|string|max:30',
            'keywords'          => 'nullable|string|max:300',
            'fields'            => 'required|array|min:1|max:12',
            'fields.*.label'    => 'required|string|max:80',
            'fields.*.type'     => 'required|in:' . implode(',', LeadForm::TYPES),
            'fields.*.options'  => 'nullable|required_if:fields.*.type,select|string|max:500',
        ]);

        $seen = [];
        $fields = [];
        foreach ($request->input('fields') as $i => $f) {
            // Reserved keys (name, email, phone, company) map onto lead columns; others become custom fields.
            $label = strtolower($d['fields'][$i]['label']);
            $key = filled($f['key'] ?? null) ? LeadForm::key($f['key']) : match (true) {
                $f['type'] === 'email' => 'email',
                $f['type'] === 'tel' => 'phone',
                str_contains($label, 'company') || str_contains($label, 'business name') => 'company',
                str_contains($label, 'name') => 'name',
                default => LeadForm::key($label),
            };
            while (isset($seen[$key])) $key .= '_' . $i;
            $seen[$key] = true;
            $fields[] = ['key' => $key, 'label' => $d['fields'][$i]['label'], 'type' => $f['type'], 'required' => ! empty($f['required']), 'options' => $f['options'] ?? ''];
        }

        $widget->update(['lead_form' => [
            'mode' => $d['mode'], 'title' => $d['title'] ?? null, 'intro' => $d['intro'] ?? null,
            'button' => $d['button'] ?? null, 'keywords' => $d['keywords'] ?? null, 'fields' => $fields,
        ]]);

        return back()->with('success', 'Lead form saved.');
    }
}
