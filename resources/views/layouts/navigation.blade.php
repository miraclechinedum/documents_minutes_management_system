<nav x-data="{ mobileSidebarOpen: false }" class="bg-white border-b border-gray-100 sm:ml-64">
    <!-- Primary Navigation Bar (shifted right on sm+ via sm:ml-64 so it starts where the sidebar ends) -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <div class="flex items-center space-x-4">
                <!-- Mobile: sidebar toggle -->
                <button @click="mobileSidebarOpen = true"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 sm:hidden"
                    aria-label="Open sidebar">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Logo: hide on small screens as requested -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <div class="text-xl font-bold text-primary-600 hidden sm:block">{{ config('app.name') }}</div>
                    </a>
                </div>

                <!-- NOTE: Desktop nav links intentionally removed (available in sidebar on desktop) -->
            </div>

            <!-- Right side -->
            <div class="flex items-center space-x-4">
                <!-- Notifications icon -->
                <div class="relative">
                    <a href="#" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                            </path>
                        </svg>

                        @php
                        $unreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
                        @endphp

                        @if($unreadCount > 0)
                        <span
                            class="absolute -top-1 -right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-medium leading-none text-white bg-red-600 rounded-full">
                            {{ $unreadCount }}
                        </span>
                        @endif
                    </a>
                </div>

                <!-- User dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                        <div>{{ Auth::user()->name }}</div>
                        <div class="ml-1">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    </button>

                    <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 z-50 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 py-1"
                        style="display: none;">
                        <a href="{{ route('profile.edit') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            {{ __('Profile') }}
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                {{ __('Log Out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar & Mobile Drawer -->
    <div class="relative">
        <!-- Desktop sidebar: fixed and full height on left -->
        <aside class="hidden sm:fixed sm:inset-y-0 sm:left-0 sm:w-64 sm:flex sm:flex-col z-20">
            <div class="flex min-h-0 flex-1 flex-col bg-[#0b1b3b] text-white border-r border-transparent">
                <div class="px-4 pt-6 pb-4">
                    <div class="flex items-center justify-between">
                        <div class="text-lg font-bold text-white">{{ config('app.name') }}</div>
                        <!-- Optional small logo icon could go here -->
                    </div>
                </div>

                <div class="flex flex-1 flex-col overflow-y-auto">
                    <nav class="mt-6 px-2 space-y-1">
                        <a href="{{ route('dashboard') }}"
                            class="{{ request()->routeIs('dashboard') ? 'bg-[#2563eb] text-white' : 'text-gray-200 hover:bg-[#2563eb] hover:text-white' }} group flex items-center px-3 py-2 text-sm font-medium rounded-md">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M13 5v6h6" />
                            </svg>
                            Dashboard
                        </a>

                        <a href="{{ route('documents.index') }}"
                            class="{{ request()->routeIs('documents.*') ? 'bg-[#2563eb] text-white' : 'text-gray-200 hover:bg-[#2563eb] hover:text-white' }} group flex items-center px-3 py-2 text-sm font-medium rounded-md">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 8h10M7 12h10M7 16h10M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H9l-4 4v12a2 2 0 002 2z" />
                            </svg>
                            Documents
                        </a>

                        <a href="{{ route('search.index') }}"
                            class="{{ request()->routeIs('search.*') ? 'bg-[#2563eb] text-white' : 'text-gray-200 hover:bg-[#2563eb] hover:text-white' }} group flex items-center px-3 py-2 text-sm font-medium rounded-md">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Search
                        </a>

                        <a href="#"
                            class="{{ request()->routeIs('notifications.*') ? 'bg-[#2563eb] text-white' : 'text-gray-200 hover:bg-[#2563eb] hover:text-white' }} group flex items-center px-3 py-2 text-sm font-medium rounded-md">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            Notifications
                            @if($unreadCount > 0)
                            <span
                                class="ml-auto inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium leading-none text-red-700 bg-red-100 rounded-full">
                                {{ $unreadCount }}
                            </span>
                            @endif
                        </a>

                        <!-- Configuration dropdown (desktop) -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open"
                                class="w-full flex items-center justify-between text-sm font-medium rounded-md px-3 py-2 text-gray-200 hover:bg-[#2563eb] hover:text-white">
                                <span class="flex items-center">
                                    <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10.325 4.317a9 9 0 119.358 0l-.47 1.173A2 2 0 0116.03 7h-2.06a2 2 0 01-1.783-1.11L10.325 4.317z" />
                                    </svg>
                                    Configuration
                                </span>
                                <svg :class="open ? 'transform rotate-90' : ''" class="h-4 w-4 text-gray-300"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M6 6L14 10L6 14V6Z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" @click.away="open = false"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 transform -translate-y-1"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                class="mt-2 space-y-1 px-2">
                                <a href="#"
                                    class="block px-3 py-2 text-sm text-gray-200 rounded-md hover:bg-[#2563eb] hover:text-white">Add
                                    Department</a>
                                <a href="#"
                                    class="block px-3 py-2 text-sm text-gray-200 rounded-md hover:bg-[#2563eb] hover:text-white">Add
                                    User</a>
                                <a href="#"
                                    class="block px-3 py-2 text-sm text-gray-200 rounded-md hover:bg-[#2563eb] hover:text-white">Add
                                    Permission</a>
                            </div>
                        </div>
                    </nav>
                </div>

                <!-- bottom logout area -->
                <div class="mt-auto px-4 py-4 border-t border-[#18304f]">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-md text-gray-200 hover:bg-[#2563eb] hover:text-white">
                            <span class="flex items-center">
                                <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 11-4 0v-1m0-8V7a2 2 0 114 0v1" />
                                </svg>
                                Log Out
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Mobile sidebar (drawer) - now matches desktop visuals -->
        <div x-show="mobileSidebarOpen" class="sm:hidden">
            <div class="fixed inset-0 z-40 flex">
                <div @click="mobileSidebarOpen = false" x-show="mobileSidebarOpen" x-transition.opacity
                    class="fixed inset-0 bg-black bg-opacity-25" aria-hidden="true"></div>

                <aside x-show="mobileSidebarOpen" x-transition:enter="transition ease-in-out duration-200 transform"
                    x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in-out duration-200 transform"
                    x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                    class="relative z-50 flex w-64 max-w-xs flex-1 flex-col bg-[#0b1b3b] text-white">
                    <div class="px-4 pt-5 pb-4">
                        <div class="flex items-center justify-between">
                            <div class="text-lg font-bold text-white">{{ config('app.name') }}</div>
                            <button @click="mobileSidebarOpen = false"
                                class="p-2 rounded-md text-white hover:bg-[#18304f]">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto">
                        <nav class="px-2 py-4 space-y-1">
                            <a href="{{ route('dashboard') }}"
                                class="{{ request()->routeIs('dashboard') ? 'bg-[#2563eb] text-white' : 'text-gray-200 hover:bg-[#2563eb] hover:text-white' }} group flex items-center px-3 py-2 text-sm font-medium rounded-md">
                                <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 12l2-2m0 0l7-7 7 7M13 5v6h6" />
                                </svg>
                                Dashboard
                            </a>

                            <a href="{{ route('documents.index') }}"
                                class="{{ request()->routeIs('documents.*') ? 'bg-[#2563eb] text-white' : 'text-gray-200 hover:bg-[#2563eb] hover:text-white' }} group flex items-center px-3 py-2 text-sm font-medium rounded-md">
                                <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 8h10M7 12h10M7 16h10M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H9l-4 4v12a2 2 0 002 2z" />
                                </svg>
                                Documents
                            </a>

                            <a href="{{ route('search.index') }}"
                                class="{{ request()->routeIs('search.*') ? 'bg-[#2563eb] text-white' : 'text-gray-200 hover:bg-[#2563eb] hover:text-white' }} group flex items-center px-3 py-2 text-sm font-medium rounded-md">
                                <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Search
                            </a>

                            <a href="#"
                                class="{{ request()->routeIs('notifications.*') ? 'bg-[#2563eb] text-white' : 'text-gray-200 hover:bg-[#2563eb] hover:text-white' }} group flex items-center px-3 py-2 text-sm font-medium rounded-md">
                                <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                Notifications
                                @if($unreadCount > 0)
                                <span
                                    class="ml-auto inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium leading-none text-red-700 bg-red-100 rounded-full">
                                    {{ $unreadCount }}
                                </span>
                                @endif
                            </a>

                            <!-- Mobile Configuration -->
                            <div x-data="{ open: false }" class="mt-2">
                                <button @click="open = !open"
                                    class="w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-200 hover:bg-[#2563eb] hover:text-white">
                                    Configuration
                                </button>
                                <div x-show="open" x-transition class="mt-1 space-y-1 pl-4">
                                    <a href="#"
                                        class="block px-3 py-2 rounded-md text-sm font-medium text-gray-200 hover:bg-[#2563eb] hover:text-white">Add
                                        Department</a>
                                    <a href="#"
                                        class="block px-3 py-2 rounded-md text-sm font-medium text-gray-200 hover:bg-[#2563eb] hover:text-white">Add
                                        User</a>
                                    <a href="#"
                                        class="block px-3 py-2 rounded-md text-sm font-medium text-gray-200 hover:bg-[#2563eb] hover:text-white">Add
                                        Permission</a>
                                </div>
                            </div>
                        </nav>
                    </div>

                    <!-- bottom logout area inside mobile drawer -->
                    <div class="mt-auto px-4 py-4 border-t border-[#18304f]">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-md text-gray-200 hover:bg-[#2563eb] hover:text-white">
                                <span class="flex items-center">
                                    <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 11-4 0v-1m0-8V7a2 2 0 114 0v1" />
                                    </svg>
                                    Log Out
                                </span>
                            </button>
                        </form>
                    </div>
                </aside>

                <div class="w-14 flex-shrink-0" aria-hidden="true"></div>
            </div>
        </div>
    </div>
</nav>