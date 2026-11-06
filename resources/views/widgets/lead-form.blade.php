@extends('layouts.app')
@section('title', 'Lead form: ' . $widget->name)
@section('page-title', 'Lead form')
@section('content')
@php $field = 'w-full bg-white/5 border border-white/10 rounded-xl px-3.5 py-2.5 text-sm text-white'; @endphp
<form method="POST" action="{{ route('widgets.leadform.save', $widget) }}" class="grid lg:grid-cols-[minmax(0,1fr)_340px] gap-6"
      x-data="{ mode: @js($form['mode']), title: @js($form['title']), intro: @js($form['intro']), button: @js($form['button']),
                fields: @js($form['fields']),
                presets: {
                    classic: [{label:'Your name',type:'text',required:false},{label:'Email address',type:'email',required:true}],
                    b2b: [{label:'Your name',type:'text',required:true},{label:'Work email',type:'email',required:true},{label:'Company',type:'text',required:false},{label:'Team size',type:'select',required:false,options:'1-10, 11-50, 51-200, 200+'}],
                    support: [{label:'Your name',type:'text',required:false},{label:'Email address',type:'email',required:true},{label:'Order number',type:'text',required:false},{label:'How can we help?',type:'textarea',required:false}],
                    gdpr: [{label:'Email address',type:'email',required:true},{label:'I agree to be contacted about my enquiry',type:'checkbox',required:true}]
                },
                move(i, d) { const j = i + d; if (j < 0 || j >= this.fields.length) return; const f = this.fields.splice(i, 1)[0]; this.fields.splice(j, 0, f); } }">
    @csrf @method('PUT')
    <div class="space-y-6 min-w-0">
        <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 sm:p-6 space-y-3">
            <h3 class="text-base font-semibold text-white">When to ask</h3>
            <div class="grid sm:grid-cols-2 gap-2">
                @foreach(\App\Support\LeadForm::MODES as $k => $label)
                <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer text-sm" :class="mode === '{{ $k }}' ? 'border-brand-500/60 bg-brand-500/10 text-white' : 'border-white/5 bg-white/[0.02] text-gray-300'">
                    <input type="radio" name="mode" value="{{ $k }}" x-model="mode"> {{ $label }}
                </label>
                @endforeach
            </div>
            <input name="keywords" x-show="mode === 'keyword'" value="{{ $form['keywords'] }}" maxlength="300" placeholder="Keywords, comma separated" class="{{ $field }}">
            <p x-show="mode === 'pre_chat'" class="text-xs text-amber-400">Visitors must fill this in before they can chat. Higher capture, but some visitors leave.</p>
        </section>

        <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 sm:p-6 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-base font-semibold text-white">Fields</h3>
                <div class="flex flex-wrap gap-1.5 text-xs">
                    <span class="text-gray-500 self-center">Start from:</span>
                    <button type="button" @click="fields = JSON.parse(JSON.stringify(presets.classic))" class="px-2.5 py-1 rounded-lg bg-white/5 text-gray-200 hover:bg-white/10">Classic</button>
                    <button type="button" @click="fields = JSON.parse(JSON.stringify(presets.b2b))" class="px-2.5 py-1 rounded-lg bg-white/5 text-gray-200 hover:bg-white/10">B2B</button>
                    <button type="button" @click="fields = JSON.parse(JSON.stringify(presets.support))" class="px-2.5 py-1 rounded-lg bg-white/5 text-gray-200 hover:bg-white/10">Support</button>
                    <button type="button" @click="fields = JSON.parse(JSON.stringify(presets.gdpr))" class="px-2.5 py-1 rounded-lg bg-white/5 text-gray-200 hover:bg-white/10">GDPR-friendly</button>
                </div>
            </div>
            <template x-for="(f, i) in fields" :key="i">
                <div class="grid grid-cols-[auto_minmax(0,1fr)] sm:grid-cols-[auto_minmax(0,1fr)_150px_auto] gap-2 items-center p-3 rounded-xl border border-white/5 bg-white/[0.02]">
                    <div class="flex flex-col">
                        <button type="button" @click="move(i, -1)" class="text-gray-500 hover:text-white text-xs leading-none" aria-label="Move up">▲</button>
                        <button type="button" @click="move(i, 1)" class="text-gray-500 hover:text-white text-xs leading-none" aria-label="Move down">▼</button>
                    </div>
                    <input :name="`fields[${i}][label]`" x-model="f.label" required maxlength="80" placeholder="Label" class="{{ $field }}">
                    <input type="hidden" :name="`fields[${i}][key]`" :value="f.key || ''">
                    <select :name="`fields[${i}][type]`" x-model="f.type" class="{{ $field }} col-span-2 sm:col-span-1">
                        @foreach(['text' => 'Text', 'email' => 'Email', 'tel' => 'Phone', 'textarea' => 'Long text', 'select' => 'Choice', 'checkbox' => 'Checkbox'] as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                    <div class="col-span-2 sm:col-span-1 flex items-center gap-3">
                        <label class="flex items-center gap-1.5 text-xs text-gray-300"><input type="checkbox" :name="`fields[${i}][required]`" value="1" x-model="f.required"> Required</label>
                        <button type="button" @click="fields.splice(i, 1)" x-show="fields.length > 1" class="text-xs text-rose-400">Remove</button>
                    </div>
                    <input x-show="f.type === 'select'" :name="`fields[${i}][options]`" x-model="f.options" maxlength="500" placeholder="Choices, comma separated" class="{{ $field }} col-span-2 sm:col-span-4">
                </div>
            </template>
            <button type="button" x-show="fields.length < 12" @click="fields.push({ label: '', type: 'text', required: false, options: '' })" class="px-4 py-2 rounded-lg bg-white/5 text-gray-200 text-xs font-semibold hover:bg-white/10">+ Add field</button>
        </section>

        <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 sm:p-6 grid sm:grid-cols-2 gap-3">
            <input name="title" x-model="title" maxlength="120" placeholder="Title" class="{{ $field }}">
            <input name="button" x-model="button" maxlength="30" placeholder="Button label" class="{{ $field }}">
            <input name="intro" x-model="intro" maxlength="300" placeholder="Short intro" class="{{ $field }} sm:col-span-2">
        </section>

        <button class="w-full sm:w-auto px-6 py-3 rounded-xl bg-brand-500 text-white text-sm font-semibold">Save lead form</button>
    </div>

    {{-- Live preview --}}
    <aside class="lg:sticky lg:top-28 self-start rounded-2xl border border-white/5 bg-gray-900/50 p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Preview</p>
        <div class="mt-3 rounded-2xl bg-[#0f0f13] p-4 text-[13px] text-gray-100 border border-white/10" x-show="mode !== 'off'">
            <p class="font-semibold" x-text="title || 'Where can we reach you?'"></p>
            <p class="mt-1 text-gray-400 text-xs" x-text="intro"></p>
            <template x-for="f in fields">
                <div class="mt-2">
                    <template x-if="f.type === 'checkbox'"><label class="flex gap-2 text-xs"><input type="checkbox" disabled> <span x-text="f.label"></span></label></template>
                    <template x-if="f.type !== 'checkbox'"><div class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs text-gray-400" :class="f.type === 'textarea' && 'h-12'" x-text="(f.label || 'Field') + (f.required ? ' *' : '')"></div></template>
                </div>
            </template>
            <div class="mt-3 rounded-lg bg-brand-500 py-2 text-center text-xs font-semibold text-white" x-text="button || 'Send'"></div>
        </div>
        <p x-show="mode === 'off'" class="mt-3 text-sm text-gray-500">The assistant will not ask for contact details. AI lead scoring still runs if enabled.</p>
        <p class="mt-3 text-xs text-gray-500">Email, phone, name and company fill the lead's main fields; anything else is saved with the lead. Same email = same lead.</p>
    </aside>
</form>
@endsection
