@extends('layouts.admin')
@section('title', 'Admin: Plans')
@section('page-title', 'Manage Plans')

@section('content')

@php
$planColors = ['blue','accent','emerald','amber','rose','brand','sky','teal','orange','pink'];
@endphp

<div
    x-data="plansManager()"
    x-init="plans = @js($plans->map(fn($p) => [
        'id'                        => $p->id,
        'name'                      => $p->name,
        'slug'                      => $p->slug,
        'description'               => $p->description ?? '',
        'color'                     => $p->color ?? 'blue',
        'sort_order'                => $p->sort_order,
        'price_monthly'             => (float)$p->price_monthly,
        'price_yearly'              => (float)$p->price_yearly,
        'widgets_limit'             => $p->widgets_limit,
        'messages_per_month'        => $p->messages_per_month,
        'training_sources_limit'    => $p->training_sources_limit,
        'leads_per_month'           => $p->leads_per_month,
        'bookings_per_month'        => $p->bookings_per_month,
        'team_members_limit'        => $p->team_members_limit,
        'workflow_automations_limit'=> $p->workflow_automations_limit,
        'channels_per_widget'       => $p->features['channels_per_widget'] ?? 1,
        'channel_messages_per_month'=> $p->features['channel_messages_per_month'] ?? 100,
        'stripe_monthly_id'         => $p->stripe_monthly_id ?? '',
        'stripe_yearly_id'          => $p->stripe_yearly_id ?? '',
        'has_live_chat'             => (bool)$p->has_live_chat,
        'has_ai_insights'           => (bool)$p->has_ai_insights,
        'has_custom_branding'       => (bool)$p->has_custom_branding,
        'has_api_access'            => (bool)$p->has_api_access,
        'has_priority_support'      => (bool)$p->has_priority_support,
        'is_active'                 => (bool)$p->is_active,
        'is_popular'                => (bool)$p->is_popular,
        'subscriptions_count'       => $p->subscriptions_count,
    ])->values())"
>

{{-- ── Page Header ──────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-sm text-gray-500 mt-0.5">
            <span x-text="plans.length"></span> plans configured
        </p>
    </div>
    <button @click="openCreate()"
            class="flex items-center gap-2 px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-sm font-medium rounded-xl transition shadow-lg shadow-rose-500/20">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        New Plan
    </button>
</div>

{{-- ── Plans Grid ───────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    <template x-for="plan in [...plans].sort((a,b) => a.sort_order - b.sort_order)" :key="plan.id">
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5 flex flex-col gap-4 relative"
             :class="plan.is_popular ? 'border-brand-500/30' : ''">

            {{-- Popular badge --}}
            <template x-if="plan.is_popular">
                <div class="absolute -top-2.5 left-4">
                    <span class="px-2.5 py-0.5 bg-brand-600 text-white text-[10px] font-semibold rounded-full shadow">Popular</span>
                </div>
            </template>

            {{-- Header --}}
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold text-white shrink-0"
                         :class="`bg-${plan.color ?? 'blue'}-500/20 text-${plan.color ?? 'blue'}-400`"
                         x-text="plan.name.charAt(0).toUpperCase()"></div>
                    <div>
                        <p class="font-semibold text-white leading-none" x-text="plan.name"></p>
                        <p class="text-[10px] text-gray-600 font-mono mt-0.5" x-text="plan.slug"></p>
                    </div>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <span class="px-2 py-0.5 text-[10px] rounded-full font-medium"
                          :class="plan.is_active ? 'bg-emerald-500/15 text-emerald-400' : 'bg-gray-500/15 text-gray-500'"
                          x-text="plan.is_active ? 'Active' : 'Inactive'"></span>
                </div>
            </div>

            {{-- Pricing --}}
            <div class="flex items-baseline gap-3">
                <div>
                    <span class="text-2xl font-bold text-white" x-text="'$' + plan.price_monthly.toFixed(0)"></span>
                    <span class="text-xs text-gray-500">/mo</span>
                </div>
                <div class="text-gray-600 text-xs">·</div>
                <div>
                    <span class="text-sm font-medium text-gray-300" x-text="'$' + plan.price_yearly.toFixed(0)"></span>
                    <span class="text-xs text-gray-500">/yr</span>
                </div>
            </div>

            {{-- Limits grid --}}
            <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs">
                <template x-for="[label, val] in [
                    ['Widgets', plan.widgets_limit],
                    ['Msgs/mo', plan.messages_per_month],
                    ['Leads/mo', plan.leads_per_month],
                    ['Bookings/mo', plan.bookings_per_month],
                    ['Sources', plan.training_sources_limit],
                    ['Team', plan.team_members_limit],
                    ['Automations', plan.workflow_automations_limit],
                    ['Channels/widget', plan.channels_per_widget],
                    ['Ch. msgs/mo', plan.channel_messages_per_month],
                ]">
                    <div class="flex items-center justify-between gap-1">
                        <span class="text-gray-500" x-text="label"></span>
                        <span class="text-white font-medium tabular-nums"
                              x-text="val === -1 ? '∞' : val.toLocaleString()"></span>
                    </div>
                </template>
            </div>

            {{-- Feature flags --}}
            <div class="flex flex-wrap gap-1.5">
                <template x-for="[flag, label] in [
                    [plan.has_live_chat, 'Live Chat'],
                    [plan.has_ai_insights, 'AI Insights'],
                    [plan.has_custom_branding, 'Branding'],
                    [plan.has_api_access, 'API'],
                    [plan.has_priority_support, 'Priority'],
                ]">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium"
                          :class="flag ? 'bg-emerald-500/10 text-emerald-400' : 'bg-white/5 text-gray-600'"
                          x-text="label"></span>
                </template>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between pt-2 border-t border-white/5 mt-auto">
                <span class="text-xs text-gray-500">
                    <span x-text="plan.subscriptions_count"></span> subscriber<span x-show="plan.subscriptions_count !== 1">s</span>
                </span>
                <div class="flex items-center gap-2">
                    <button @click="openEdit(plan)"
                            class="px-3 py-1.5 text-xs font-medium bg-white/5 hover:bg-white/10 text-gray-300 hover:text-white rounded-lg transition">
                        Edit
                    </button>
                    <button @click="confirmDelete(plan)"
                            class="px-3 py-1.5 text-xs font-medium bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 hover:text-rose-300 rounded-lg transition"
                            :title="plan.subscriptions_count > 0 ? 'Has active subscribers — will be blocked' : 'Delete plan'">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Empty state --}}
    <template x-if="plans.length === 0">
        <div class="col-span-full text-center py-20 text-gray-500">
            <div class="text-4xl mb-3">💳</div>
            <p class="font-medium text-gray-400">No plans yet</p>
            <button @click="openCreate()" class="mt-4 px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-sm rounded-xl transition">
                Create First Plan
            </button>
        </div>
    </template>
</div>

{{-- ── Create / Edit Modal ──────────────────────────────────────────────── --}}
<div x-show="modalOpen" x-transition class="fixed inset-0 z-50 flex items-start justify-center p-4 overflow-y-auto"
     style="background:rgba(0,0,0,0.75)" @click.self="modalOpen = false">
    <div class="bg-gray-900 border border-white/10 rounded-2xl w-full max-w-2xl shadow-2xl my-8" @click.stop>

        {{-- Modal Header --}}
        <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-white"
                    x-text="editingId ? 'Edit Plan' : 'New Plan'"></h3>
                <p class="text-xs text-gray-500 mt-0.5"
                   x-text="editingId ? 'Update the plan details and limits.' : 'Create a new subscription plan.'"></p>
            </div>
            <button @click="modalOpen = false" class="text-gray-500 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Modal Form --}}
        <form method="POST" :action="formAction" class="p-6 space-y-5">
            @csrf
            <template x-if="editingId"><input type="hidden" name="_method" value="PUT"></template>

            {{-- Basic Info --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Plan Identity</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-400 mb-1">Plan Name <span class="text-rose-400">*</span></label>
                        <input name="name" x-model="f.name" @input="if(!editingId) generateSlug()" required
                               placeholder="e.g. Pro, Business, Enterprise"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white placeholder-gray-600 text-sm focus:outline-none focus:border-rose-500/50 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">Slug <span class="text-rose-400">*</span></label>
                        <input name="slug" x-model="f.slug" required pattern="[a-z0-9\-]+"
                               placeholder="e.g. pro"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white placeholder-gray-600 text-sm font-mono focus:outline-none focus:border-rose-500/50 transition">
                        <p class="text-[10px] text-gray-600 mt-1">Lowercase letters, numbers, hyphens only</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">Color</label>
                        <select name="color" x-model="f.color"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-rose-500/50 transition">
                            @foreach($planColors as $c)
                            <option value="{{ $c }}" class="bg-gray-900">{{ ucfirst($c) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-400 mb-1">Description</label>
                        <textarea name="description" x-model="f.description" rows="2"
                                  placeholder="Short description shown on the pricing page"
                                  class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white placeholder-gray-600 text-sm resize-none focus:outline-none focus:border-rose-500/50 transition"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">Sort Order</label>
                        <input name="sort_order" type="number" min="0" x-model.number="f.sort_order"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-rose-500/50 transition">
                    </div>
                </div>
            </div>

            {{-- Pricing --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Pricing</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">Monthly Price ($)</label>
                        <input name="price_monthly" type="number" step="0.01" min="0" x-model.number="f.price_monthly" required
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-rose-500/50 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">Yearly Price ($)</label>
                        <input name="price_yearly" type="number" step="0.01" min="0" x-model.number="f.price_yearly" required
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-rose-500/50 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">Stripe Monthly ID</label>
                        <input name="stripe_monthly_id" x-model="f.stripe_monthly_id"
                               placeholder="price_xxxxxxxxxxxxxxxx"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white placeholder-gray-600 text-sm font-mono focus:outline-none focus:border-rose-500/50 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">Stripe Yearly ID</label>
                        <input name="stripe_yearly_id" x-model="f.stripe_yearly_id"
                               placeholder="price_xxxxxxxxxxxxxxxx"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white placeholder-gray-600 text-sm font-mono focus:outline-none focus:border-rose-500/50 transition">
                    </div>
                </div>
            </div>

            {{-- Limits --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-1">Limits <span class="normal-case font-normal text-gray-600">(-1 = unlimited)</span></p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3">
                    @foreach([
                        ['widgets_limit',             'Widgets'],
                        ['messages_per_month',         'Msgs/mo'],
                        ['leads_per_month',            'Leads/mo'],
                        ['bookings_per_month',         'Bookings/mo'],
                        ['training_sources_limit',     'Sources'],
                        ['team_members_limit',         'Team'],
                        ['workflow_automations_limit', 'Automations'],
                        ['channels_per_widget',        'Channels/widget'],
                        ['channel_messages_per_month', 'Ch. msgs/mo'],
                    ] as [$field, $label])
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">{{ $label }}</label>
                        <input name="{{ $field }}" type="number" min="-1" x-model.number="f.{{ $field }}" required
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-white text-sm focus:outline-none focus:border-rose-500/50 transition">
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Feature Toggles --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Features & Status</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @foreach([
                        ['has_live_chat',       'Live Chat'],
                        ['has_ai_insights',     'AI Insights'],
                        ['has_custom_branding', 'Branding'],
                        ['has_api_access',      'API Access'],
                        ['has_priority_support','Priority'],
                        ['is_active',           'Active'],
                        ['is_popular',          'Popular'],
                    ] as [$field, $label])
                    <label class="flex items-center gap-2 p-2.5 bg-white/2 border border-white/5 rounded-xl cursor-pointer hover:bg-white/5 transition">
                        <input type="hidden" name="{{ $field }}" value="0">
                        <input type="checkbox" name="{{ $field }}" value="1"
                               x-model="f.{{ $field }}"
                               class="rounded bg-white/5 border-white/20 text-rose-600">
                        <span class="text-xs text-gray-300">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center gap-3 pt-2 border-t border-white/5">
                <button type="submit"
                        class="flex-1 px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white text-sm font-medium rounded-xl transition"
                        x-text="editingId ? 'Save Changes' : 'Create Plan'">
                </button>
                <button type="button" @click="modalOpen = false"
                        class="px-4 py-2.5 bg-white/5 hover:bg-white/10 text-gray-300 text-sm rounded-xl transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Delete Confirmation Modal ────────────────────────────────────────── --}}
<div x-show="deleteModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.75)" @click.self="deleteModal = false; deletingPlan = null">
    <div class="bg-gray-900 border border-white/10 rounded-2xl w-full max-w-sm shadow-2xl p-6" @click.stop>
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-rose-500/15 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                </svg>
            </div>
            <div>
                <h4 class="font-semibold text-white">Delete Plan?</h4>
                <p class="text-xs text-gray-500 mt-0.5">This action cannot be undone.</p>
            </div>
        </div>

        <p class="text-sm text-gray-300 mb-1">
            You are about to delete <strong class="text-white" x-text="deletingPlan?.name"></strong>.
        </p>
        <p class="text-xs text-gray-500 mb-5">
            Plans with active subscribers cannot be deleted. Deactivate the plan instead.
        </p>

        <form method="POST" :action="deleteAction">
            @csrf
            <input type="hidden" name="_method" value="DELETE">
            <div class="flex gap-2">
                <button type="submit"
                        class="flex-1 px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white text-sm font-medium rounded-xl transition">
                    Yes, Delete
                </button>
                <button type="button" @click="deleteModal = false; deletingPlan = null"
                        class="px-4 py-2.5 bg-white/5 hover:bg-white/10 text-gray-300 text-sm rounded-xl transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

</div>{{-- end x-data --}}

@push('scripts')
<script>
function plansManager() {
    return {
        plans: [],
        modalOpen: false,
        editingId: null,
        deleteModal: false,
        deletingPlan: null,

        f: {
            name: '', slug: '', description: '', color: 'blue', sort_order: 0,
            price_monthly: 0, price_yearly: 0,
            widgets_limit: 1, messages_per_month: 500, training_sources_limit: 3,
            leads_per_month: 50, bookings_per_month: 0, team_members_limit: 1, workflow_automations_limit: 0,
            channels_per_widget: 1, channel_messages_per_month: 100,
            stripe_monthly_id: '', stripe_yearly_id: '',
            has_live_chat: false, has_ai_insights: false, has_custom_branding: false,
            has_api_access: false, has_priority_support: false,
            is_active: true, is_popular: false,
        },

        get formAction() {
            return this.editingId ? '/admin/plans/' + this.editingId : '/admin/plans';
        },

        get deleteAction() {
            return this.deletingPlan ? '/admin/plans/' + this.deletingPlan.id : '#';
        },

        openCreate() {
            this.editingId = null;
            this.f = {
                name: '', slug: '', description: '', color: 'blue', sort_order: this.plans.length,
                price_monthly: 0, price_yearly: 0,
                widgets_limit: 1, messages_per_month: 500, training_sources_limit: 3,
                leads_per_month: 50, bookings_per_month: 0, team_members_limit: 1, workflow_automations_limit: 0,
                channels_per_widget: 1, channel_messages_per_month: 100,
                stripe_monthly_id: '', stripe_yearly_id: '',
                has_live_chat: false, has_ai_insights: false, has_custom_branding: false,
                has_api_access: false, has_priority_support: false,
                is_active: true, is_popular: false,
            };
            this.modalOpen = true;
        },

        openEdit(plan) {
            this.editingId = plan.id;
            this.f = { ...plan };
            this.modalOpen = true;
        },

        confirmDelete(plan) {
            this.deletingPlan = plan;
            this.deleteModal = true;
        },

        generateSlug() {
            this.f.slug = this.f.name
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-');
        },
    };
}
</script>
@endpush

@endsection
