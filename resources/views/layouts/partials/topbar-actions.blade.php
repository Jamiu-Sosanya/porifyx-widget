        {{-- Theme Toggle --}}
        <button @click="toggleTheme()"
                class="p-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-white/5 transition"
                :title="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'">
            {{-- Sun icon (shown in dark mode to switch to light) --}}
            <svg x-show="theme === 'dark'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
            </svg>
            {{-- Moon icon (shown in light mode to switch to dark) --}}
            <svg x-show="theme === 'light'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
            </svg>
        </button>

        {{-- Notifications --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="relative p-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-white/5 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                @if(auth()->user()->unreadNotifications->count() > 0)
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 rounded-full ring-2 ring-gray-900"></span>
                @endif
            </button>
            <div x-show="open" @click.away="open = false" x-transition
                 class="absolute right-0 mt-2 w-80 bg-gray-900 border border-white/10 rounded-2xl shadow-2xl overflow-hidden z-50">
                <div class="p-4 border-b border-white/5">
                    <h3 class="text-sm font-semibold text-white">Notifications</h3>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    @if(auth()->user()->notifications->count() > 0)
                        @foreach(auth()->user()->notifications->take(10) as $notification)
                        <div class="p-4 border-b border-white/5 hover:bg-white/5 transition {{ $notification->read_at ? 'opacity-60' : '' }}">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0">
                                    @if($notification->data['alert_type'] === 'conversation_volume_drop')
                                        📉
                                    @elseif($notification->data['alert_type'] === 'conversation_volume_spike')
                                        📈
                                    @elseif($notification->data['alert_type'] === 'lead_quality_drop')
                                        📊
                                    @elseif($notification->data['alert_type'] === 'hot_lead_drop')
                                        🔥
                                    @elseif($notification->data['alert_type'] === 'ab_test_significant_difference')
                                        🧪
                                    @else
                                        🔔
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-white leading-tight">{{ $notification->data['message'] ?? 'System Alert' }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                    @if(isset($notification->data['action']))
                                    <a href="{{ $notification->data['action']['url'] }}" class="inline-block mt-2 text-xs text-brand-400 hover:text-brand-300">{{ $notification->data['action']['text'] }}</a>
                                    @endif
                                </div>
                                @if(!$notification->read_at)
                                <form method="POST" action="{{ route('notifications.mark-read', $notification) }}" class="flex-shrink-0">
                                    @csrf
                                    <button class="text-gray-500 hover:text-white transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                        @endforeach
                        @if(auth()->user()->notifications->count() > 10)
                        <div class="p-4 text-center">
                            <a href="{{ route('notifications.index') }}" class="text-sm text-brand-400 hover:text-brand-300">View all notifications</a>
                        </div>
                        @endif
                    @else
                        <div class="p-4 text-sm text-gray-400 text-center py-8">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                            No notifications
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- User Menu --}}
        <div x-data="{ open: false }" class="relative">
            <button @click.prevent="open = !open" class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-white/5 transition">
                <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="w-8 h-8 rounded-lg object-cover">
                <svg class="w-4 h-4 text-gray-500 hidden sm:block" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
            </button>
            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 bg-gray-900 border border-white/10 rounded-2xl shadow-2xl overflow-hidden z-50">
                <div class="p-3 border-b border-white/5">
                    <p class="text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                </div>
                <div class="p-1">
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.profile') : route('settings.profile') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-400 hover:text-white hover:bg-white/5 rounded-xl transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                        Profile
                    </a>
                    @unless(auth()->user()->isAdmin())
                    <a href="{{ route('settings.billing') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-400 hover:text-white hover:bg-white/5 rounded-xl transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
                        Billing
                    </a>
                    @endunless
                </div>
                <div class="p-1 border-t border-white/5">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 px-3 py-2 text-sm text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 rounded-xl transition w-full">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>
