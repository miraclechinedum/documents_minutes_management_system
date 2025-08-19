{{-- <x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot> --}}

    @extends('layouts.guest')

    @section('content')

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- My Documents -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <dt class="text-sm font-medium text-gray-500 truncate">My Documents</dt>
                                <dd class="text-lg font-semibold text-gray-900">
                                    {{ Auth::user()->assignedDocuments()->count() }}
                                </dd>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Department Documents -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                        </path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <dt class="text-sm font-medium text-gray-500 truncate">Department Documents</dt>
                                <dd class="text-lg font-semibold text-gray-900">
                                    @if(Auth::user()->department)
                                    {{ Auth::user()->department->assignedDocuments()->count() }}
                                    @else
                                    0
                                    @endif
                                </dd>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Tasks -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <dt class="text-sm font-medium text-gray-500 truncate">In Progress</dt>
                                <dd class="text-lg font-semibold text-gray-900">
                                    {{ Auth::user()->assignedDocuments()->where('status', 'in_progress')->count() }}
                                </dd>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unread Notifications -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                                        </path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <dt class="text-sm font-medium text-gray-500 truncate">Notifications</dt>
                                <dd class="text-lg font-semibold text-gray-900">
                                    {{ Auth::user()->unreadNotifications->count() }}
                                </dd>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Documents -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Documents</h3>
                </div>
                <div class="divide-y divide-gray-200">
                    @php
                    $recentDocuments = \App\Models\Document::query()
                    ->with(['creator', 'assignedToUser', 'assignedToDepartment'])
                    ->where(function($q) {
                    $q->where('created_by', Auth::id())
                    ->orWhere('assigned_to_user_id', Auth::id())
                    ->orWhere('assigned_to_department_id', Auth::user()->department_id);
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                    @endphp

                    @forelse($recentDocuments as $document)
                    <div class="px-6 py-4 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $document->status_color }}">
                                        {{ ucfirst(str_replace('_', ' ', $document->status)) }}
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('documents.show', $document) }}"
                                        class="text-sm font-medium text-gray-900 hover:text-primary-600">
                                        {{ $document->title }}
                                    </a>
                                    <p class="text-sm text-gray-500">
                                        {{ $document->reference_number ? 'Ref: ' . $document->reference_number . ' • ' :
                                        '' }}
                                        Created {{ $document->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                @if($document->priority !== 'low')
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $document->priority_color }}">
                                    {{ ucfirst($document->priority) }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="px-6 py-4 text-center text-gray-500">
                        No recent documents found.
                    </div>
                    @endforelse
                </div>
                @if($recentDocuments->count() > 0)
                <div class="px-6 py-3 bg-gray-50 text-center">
                    <a href="{{ route('documents.index') }}"
                        class="text-sm font-medium text-primary-600 hover:text-primary-500">
                        View all documents →
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endsection