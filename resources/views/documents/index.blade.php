@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Documents') }}
    </h2>
    @can('create', App\Models\Document::class)
    <a href="{{ route('documents.create') }}"
        class="bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 px-4 rounded-lg">
        Upload Document
    </a>
    @endcan
</div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4">
                <form method="GET" action="{{ route('documents.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}"
                                placeholder="Title, reference, filename..."
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                        </div>

                        <!-- Filter -->
                        <div>
                            <label for="filter" class="block text-sm font-medium text-gray-700">Filter</label>
                            <select name="filter" id="filter"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                <option value="">All Documents</option>
                                <option value="my_docs" {{ request('filter')==='my_docs' ? 'selected' : '' }}>My
                                    Documents</option>
                                <option value="dept_docs" {{ request('filter')==='dept_docs' ? 'selected' : '' }}>
                                    Department Documents</option>
                                <option value="created_by_me" {{ request('filter')==='created_by_me' ? 'selected' : ''
                                    }}>Created by Me</option>
                            </select>
                        </div>

                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                <option value="">All Statuses</option>
                                <option value="received" {{ request('status')==='received' ? 'selected' : '' }}>
                                    Received</option>
                                <option value="in_progress" {{ request('status')==='in_progress' ? 'selected' : '' }}>In
                                    Progress</option>
                                <option value="completed" {{ request('status')==='completed' ? 'selected' : '' }}>
                                    Completed</option>
                                <option value="quarantined" {{ request('status')==='quarantined' ? 'selected' : '' }}>
                                    Quarantined</option>
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700">Date From</label>
                            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                        </div>
                    </div>

                    <div class="flex justify-between items-center">
                        <div class="flex space-x-2">
                            <button type="submit"
                                class="bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 px-4 rounded">
                                Apply Filters
                            </button>
                            <a href="{{ route('documents.index') }}"
                                class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-bold py-2 px-4 rounded">
                                Clear
                            </a>
                        </div>

                        <div class="flex items-center space-x-2">
                            <label for="sort" class="text-sm text-gray-700">Sort by:</label>
                            <select name="sort" id="sort" onchange="this.form.submit()"
                                class="rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                <option value="created_at" {{ request('sort', 'created_at' )==='created_at' ? 'selected'
                                    : '' }}>Created Date</option>
                                <option value="title" {{ request('sort')==='title' ? 'selected' : '' }}>Title
                                </option>
                                <option value="due_date" {{ request('sort')==='due_date' ? 'selected' : '' }}>Due
                                    Date</option>
                                <option value="priority" {{ request('sort')==='priority' ? 'selected' : '' }}>
                                    Priority</option>
                            </select>
                            <select name="direction" onchange="this.form.submit()"
                                class="rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                <option value="desc" {{ request('direction', 'desc' )==='desc' ? 'selected' : '' }}>
                                    Descending</option>
                                <option value="asc" {{ request('direction')==='asc' ? 'selected' : '' }}>Ascending
                                </option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Documents Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($documents as $document)
            <div class="bg-white overflow-hidden shadow-sm rounded-lg hover:shadow-md transition-shadow duration-200">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                <a href="{{ route('documents.show', $document) }}" class="hover:text-primary-600">
                                    {{ $document->title }}
                                </a>
                            </h3>
                            @if($document->reference_number)
                            <p class="text-sm text-gray-500 mb-2">Ref: {{ $document->reference_number }}</p>
                            @endif
                        </div>
                        <div class="flex flex-col items-end space-y-1">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $document->status_color }}">
                                {{ ucfirst(str_replace('_', ' ', $document->status)) }}
                            </span>
                            @if($document->priority !== 'low')
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $document->priority_color }}">
                                {{ ucfirst($document->priority) }}
                            </span>
                            @endif
                        </div>
                    </div>

                    <div class="text-sm text-gray-600 space-y-1">
                        <p>Created by {{ $document->creator->name }}</p>
                        <p>{{ $document->created_at->format('M j, Y') }}</p>
                        @if($document->getCurrentAssignee())
                        <p>Assigned to {{ $document->getCurrentAssignee() }}</p>
                        @endif
                        @if($document->due_date)
                        <p class="text-red-600">Due: {{ $document->due_date->format('M j, Y') }}</p>
                        @endif
                    </div>

                    <div class="mt-4 flex justify-between items-center">
                        <div class="text-xs text-gray-500">
                            {{ $document->file_name }} ({{ number_format($document->file_size / 1024, 1) }} KB)
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ route('documents.show', $document) }}"
                                class="text-primary-600 hover:text-primary-500 text-sm font-medium">
                                View
                            </a>
                            @can('export', $document)
                            <a href="{{ route('documents.export', $document) }}"
                                class="text-secondary-600 hover:text-secondary-500 text-sm font-medium">
                                Export
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-full bg-white rounded-lg shadow p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No documents found</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by uploading a document.</p>
                @can('create', App\Models\Document::class)
                <div class="mt-6">
                    <a href="{{ route('documents.create') }}"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Upload Document
                    </a>
                </div>
                @endcan
            </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($documents->hasPages())
        <div class="mt-8">
            {{ $documents->links() }}
        </div>
        @endif
    </div>
</div>
@endsection