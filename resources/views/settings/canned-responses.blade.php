@extends('layouts.app')
@section('title', 'Canned Responses')
@section('page-title', 'Canned Responses')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="cannedResponses()">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Canned Responses</h1>
            <p class="text-sm text-gray-400 mt-1">Pre-written replies you can insert in conversations. Type a shortcut (e.g. <code class="text-brand-400">/refund</code>) in the chat to trigger them.</p>
        </div>
        <button @click="openCreate()" class="flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            New Response
        </button>
    </div>

    {{-- Category filter --}}
    @if($categories->count() > 0)
    <div class="flex gap-2 flex-wrap">
        <button @click="activeCategory = null"
                :class="activeCategory === null ? 'bg-brand-600 text-white' : 'bg-white/5 text-gray-400 hover:text-white'"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition">All</button>
        @foreach($categories as $cat)
        <button @click="activeCategory = '{{ $cat }}'"
                :class="activeCategory === '{{ $cat }}' ? 'bg-brand-600 text-white' : 'bg-white/5 text-gray-400 hover:text-white'"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition">{{ ucfirst($cat) }}</button>
        @endforeach
    </div>
    @endif

    {{-- Responses list --}}
    @if($responses->isEmpty())
    <div class="flex flex-col items-center justify-center py-20 text-center bg-white/3 border border-white/5 rounded-2xl">
        <svg class="w-12 h-12 text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
        <p class="text-gray-400 font-medium">No canned responses yet</p>
        <p class="text-gray-600 text-sm mt-1">Create your first one to speed up your replies.</p>
        <button @click="openCreate()" class="mt-4 px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition">Create Response</button>
    </div>
    @else
    <div class="space-y-3">
        @foreach($responses as $response)
        <div class="bg-white/3 border border-white/5 rounded-2xl p-5 hover:border-white/10 transition"
             x-show="activeCategory === null || activeCategory === '{{ $response->category }}'">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="text-sm font-semibold text-white">{{ $response->title }}</span>
                        @if($response->shortcut)
                        <span class="px-2 py-0.5 bg-brand-500/10 text-brand-400 text-xs font-mono rounded-md border border-brand-500/20">{{ $response->shortcut }}</span>
                        @endif
                        <span class="px-2 py-0.5 bg-white/5 text-gray-400 text-xs rounded-md">{{ ucfirst($response->category) }}</span>
                        @if($response->use_count > 0)
                        <span class="text-xs text-gray-500">Used {{ $response->use_count }}×</span>
                        @endif
                    </div>
                    <p class="mt-2 text-sm text-gray-400 line-clamp-2">{{ $response->content }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button @click="openEdit({{ $response->id }}, {{ json_encode($response->title) }}, {{ json_encode($response->shortcut ?? '') }}, {{ json_encode($response->category) }}, {{ json_encode($response->content) }})"
                            class="p-2 text-gray-400 hover:text-brand-400 hover:bg-brand-500/10 rounded-lg transition" title="Edit">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
                    </button>
                    <form method="POST" action="{{ route('settings.cannedResponses.delete', $response) }}" onsubmit="return confirm('Delete this canned response?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-2 text-gray-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition" title="Delete">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Create / Edit Modal --}}
    <div x-show="modalOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @keydown.escape.window="modalOpen = false">
        <div class="bg-gray-900 border border-white/10 rounded-2xl w-full max-w-lg shadow-2xl" @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/5">
                <h2 class="text-base font-semibold text-white" x-text="editId ? 'Edit Canned Response' : 'New Canned Response'"></h2>
                <button @click="modalOpen = false" class="text-gray-400 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <form id="canned-form" method="POST" action="{{ route('settings.cannedResponses.store') }}" class="p-6 space-y-4">
                @csrf
                {{-- action / _method / _edit_id are set imperatively by openCreate() / openEdit() --}}

                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Title <span class="text-rose-400">*</span></label>
                    <input type="text" name="title" x-model="form.title" required maxlength="150"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-brand-500 transition"
                           placeholder="e.g. Refund Policy">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Shortcut <span class="text-gray-500">(optional)</span></label>
                        <input type="text" name="shortcut" x-model="form.shortcut" maxlength="50"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-brand-500 transition font-mono"
                               placeholder="/refund">
                        <p class="mt-1 text-xs text-gray-500">Must start with /</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Category <span class="text-rose-400">*</span></label>
                        <input type="text" name="category" x-model="form.category" required maxlength="50"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-brand-500 transition"
                               placeholder="general" list="category-list">
                        <datalist id="category-list">
                            @foreach($categories as $cat)<option value="{{ $cat }}">@endforeach
                        </datalist>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Content <span class="text-rose-400">*</span></label>
                    <textarea name="content" x-model="form.content" required maxlength="2000" rows="5"
                              class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-brand-500 transition resize-none"
                              placeholder="Write your canned response here..."></textarea>
                    <p class="mt-1 text-xs text-gray-500" x-text="`${form.content.length} / 2000 characters`"></p>
                </div>

                @error('title') <p class="text-rose-400 text-xs">{{ $message }}</p> @enderror
                @error('shortcut') <p class="text-rose-400 text-xs">{{ $message }}</p> @enderror
                @error('category') <p class="text-rose-400 text-xs">{{ $message }}</p> @enderror
                @error('content') <p class="text-rose-400 text-xs">{{ $message }}</p> @enderror

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="modalOpen = false"
                            class="px-4 py-2 text-sm text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 rounded-xl transition">Cancel</button>
                    <button type="submit"
                            class="px-5 py-2 text-sm font-medium bg-brand-600 hover:bg-brand-500 text-white rounded-xl transition">
                        <span x-text="editId ? 'Save Changes' : 'Create Response'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function cannedResponses() {
    const STORE_URL = '{{ route('settings.cannedResponses.store') }}';

    // Restore state if we bounced back from a validation error
    const hasErrors   = {{ $errors->any() ? 'true' : 'false' }};
    const oldTitle    = @json(old('title', ''));
    const oldShortcut = @json(old('shortcut', ''));
    const oldCategory = @json(old('category', 'general'));
    const oldContent  = @json(old('content', ''));
    const oldEditId   = {{ old('_edit_id') ? (int) old('_edit_id') : 'null' }};

    // Imperatively configure the form — called before the modal opens so the DOM
    // is correct before the user can interact. No reliance on Alpine's async patching.
    function applyFormMode(editId) {
        const form = document.getElementById('canned-form');
        if (!form) return;

        form.action = editId ? `/settings/canned-responses/${editId}` : STORE_URL;

        let methodEl = form.querySelector('input[name="_method"]');
        let editIdEl = form.querySelector('input[name="_edit_id"]');

        if (editId) {
            if (!methodEl) {
                methodEl = Object.assign(document.createElement('input'), { type: 'hidden', name: '_method' });
                form.appendChild(methodEl);
            }
            methodEl.value = 'PUT';

            if (!editIdEl) {
                editIdEl = Object.assign(document.createElement('input'), { type: 'hidden', name: '_edit_id' });
                form.appendChild(editIdEl);
            }
            editIdEl.value = editId;
        } else {
            methodEl?.remove();
            editIdEl?.remove();
        }
    }

    return {
        modalOpen: hasErrors && oldTitle !== '',
        editId: hasErrors ? oldEditId : null,
        activeCategory: null,
        form: hasErrors
            ? { title: oldTitle, shortcut: oldShortcut, category: oldCategory, content: oldContent }
            : { title: '', shortcut: '', category: 'general', content: '' },

        init() {
            if (hasErrors && oldTitle !== '') {
                this.$nextTick(() => applyFormMode(oldEditId));
            }
        },

        openCreate() {
            this.editId = null;
            this.form = { title: '', shortcut: '', category: 'general', content: '' };
            applyFormMode(null);
            this.modalOpen = true;
        },

        openEdit(id, title, shortcut, category, content) {
            this.editId = id;
            this.form = { title, shortcut, category, content };
            applyFormMode(id);
            this.modalOpen = true;
        },
    };
}
</script>
@endpush
