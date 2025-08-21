@extends('layouts.app')

@section('header')
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ $document->title }} - Export with Minutes
    </h2>
    <a href="{{ route('documents.show', $document) }}"
        class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 text-center">
        Back to Document
    </a>
</div>
@endsection

@section('content')
<div class="py-8">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-900">Document Export with Minutes</h3>
                <p class="mt-1 text-sm text-gray-600">Complete document information and all associated minutes.</p>
            </div>
            <div class="p-8">
                <!-- Document Information Section -->
                <div class="space-y-6">
                    <div class="border-b border-gray-200 pb-4">
                        <h4 class="text-lg font-medium text-gray-900">Document Information</h4>
                        <p class="mt-1 text-sm text-gray-600">Basic details about this document.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm font-medium text-gray-500">Status:</span>
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $document->status_color ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst(str_replace('_', ' ', $document->status)) }}
                                </span>
                            </div>

                            @if($document->reference_number)
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm font-medium text-gray-500">Reference:</span>
                                <span class="text-sm text-gray-900 font-mono">{{ $document->reference_number }}</span>
                            </div>
                            @endif

                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm font-medium text-gray-500">Priority:</span>
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $document->priority_color ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($document->priority) }}
                                </span>
                            </div>

                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm font-medium text-gray-500">Created By:</span>
                                <span class="text-sm text-gray-900">{{ $document->creator->name }}</span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm font-medium text-gray-500">Created On:</span>
                                <span class="text-sm text-gray-900">{{ $document->created_at->format('M j, Y \a\t g:i
                                    A') }}</span>
                            </div>

                            @if($document->getCurrentAssignee())
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm font-medium text-gray-500">Assigned To:</span>
                                <span class="text-sm text-gray-900">{{ $document->getCurrentAssignee() }}</span>
                            </div>
                            @endif

                            @if($document->due_date)
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm font-medium text-gray-500">Due Date:</span>
                                <span class="text-sm text-red-600 font-medium">{{ $document->due_date->format('M j, Y')
                                    }}</span>
                            </div>
                            @endif

                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm font-medium text-gray-500">File Name:</span>
                                <span class="text-sm text-gray-900 font-mono">{{ $document->file_name }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm font-medium text-gray-500">File Size:</span>
                            <span class="text-sm text-gray-900">{{ number_format($document->file_size / 1024, 1) }}
                                KB</span>
                        </div>

                        @if($document->pages)
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm font-medium text-gray-500">Pages:</span>
                            <span class="text-sm text-gray-900">{{ $document->pages }}</span>
                        </div>
                        @endif
                    </div>

                    @if($document->description)
                    <div class="mt-6">
                        <div class="text-sm font-medium text-gray-500 mb-2">Description:</div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-900 leading-relaxed">{{ $document->description }}</p>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Minutes Section -->
                <div class="space-y-6 mt-8">
                    <div class="border-b border-gray-200 pb-4">
                        <h4 class="text-lg font-medium text-gray-900">Minutes & Annotations ({{ $minutes->count() }}
                            total)</h4>
                        <p class="mt-1 text-sm text-gray-600">All minutes and comments associated with this document.
                        </p>
                    </div>

                    @forelse($minutes as $minute)
                    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-4">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                        <span class="text-sm font-medium text-gray-700">{{
                                            substr($minute->creator->name, 0, 1) }}</span>
                                    </div>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">{{ $minute->creator->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $minute->created_at->format('M j, Y \a\t g:i
                                        A') }}</div>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ $minute->visibility === 'public' ? 'bg-green-100 text-green-800' : 
                                       ($minute->visibility === 'department' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ ucfirst($minute->visibility) }}
                                </span>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4 mb-3">
                            <div class="text-gray-900 leading-relaxed">{{ $minute->body }}</div>
                        </div>

                        @if($minute->hasOverlay())
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-3 mb-3">
                            <div class="flex items-center text-sm text-purple-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                This minute is positioned on page {{ $minute->page_number }} of the document
                            </div>
                        </div>
                        @endif

                        @if($minute->getForwardedToName())
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <div class="flex items-center text-sm text-blue-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7l5 5-5 5M6 12h12"></path>
                                </svg>
                                Forwarded to {{ $minute->getForwardedToName() }}
                            </div>
                        </div>
                        @endif
                    </div>
                    @empty
                    <div class="text-center py-12 bg-gray-50 rounded-lg">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                            </path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No minutes have been added to this document
                            yet</h3>
                        <p class="mt-1 text-sm text-gray-500">This document doesn't have any associated minutes or
                            comments.</p>
                    </div>
                    @endforelse
                </div>

                <!-- Footer -->
                <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                    <p class="text-sm text-gray-500">Generated on {{ now()->format('M j, Y \a\t g:i A') }}</p>
                    <p class="text-sm text-gray-500">Document Management System - {{ config('app.name') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection