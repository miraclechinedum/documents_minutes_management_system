<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $document->title }}
            </h2>
            <div class="flex space-x-2">
                @can('update', $document)
                    <a href="{{ route('documents.edit', $document) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Edit
                    </a>
                @endcan
                @can('export', $document)
                    <a href="{{ route('documents.export', $document) }}" class="bg-secondary-600 hover:bg-secondary-700 text-white font-bold py-2 px-4 rounded">
                        Export PDF
                    </a>
                @endcan
                <a href="{{ route('documents.download', $document) }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Download Original
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Document Details -->
                <div class="lg:col-span-1">
                    <div class="bg-white shadow rounded-lg p-6 mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Document Details</h3>
                        
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $document->status_color }}">
                                        {{ ucfirst(str_replace('_', ' ', $document->status)) }}
                                    </span>
                                </dd>
                            </div>

                            @if($document->reference_number)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Reference Number</dt>
                                    <dd class="text-sm text-gray-900">{{ $document->reference_number }}</dd>
                                </div>
                            @endif

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Priority</dt>
                                <dd>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $document->priority_color }}">
                                        {{ ucfirst($document->priority) }}
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Created By</dt>
                                <dd class="text-sm text-gray-900">{{ $document->creator->name }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Created On</dt>
                                <dd class="text-sm text-gray-900">{{ $document->created_at->format('M j, Y \a\t g:i A') }}</dd>
                            </div>

                            @if($document->getCurrentAssignee())
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Assigned To</dt>
                                    <dd class="text-sm text-gray-900">{{ $document->getCurrentAssignee() }}</dd>
                                </div>
                            @endif

                            @if($document->due_date)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Due Date</dt>
                                    <dd class="text-sm text-gray-900">{{ $document->due_date->format('M j, Y') }}</dd>
                                </div>
                            @endif

                            <div>
                                <dt class="text-sm font-medium text-gray-500">File Info</dt>
                                <dd class="text-sm text-gray-900">
                                    {{ $document->file_name }}<br>
                                    {{ number_format($document->file_size / 1024, 1) }} KB
                                    @if($document->pages)
                                        • {{ $document->pages }} page(s)
                                    @endif
                                </dd>
                            </div>

                            @if($document->description)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                                    <dd class="text-sm text-gray-900">{{ $document->description }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    <!-- Routing History -->
                    @if($document->routes->count() > 0)
                        <div class="bg-white shadow rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Routing History</h3>
                            <div class="flow-root">
                                <ul class="-mb-8">
                                    @foreach($document->routes as $route)
                                        <li>
                                            <div class="relative pb-8">
                                                @if(!$loop->last)
                                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                                @endif
                                                <div class="relative flex space-x-3">
                                                    <div>
                                                        <span class="h-8 w-8 rounded-full bg-primary-500 flex items-center justify-center ring-8 ring-white">
                                                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5-5 5M6 12h12"></path>
                                                            </svg>
                                                        </span>
                                                    </div>
                                                    <div class="min-w-0 flex-1 pt-1.5">
                                                        <div>
                                                            <p class="text-sm text-gray-500">
                                                                Forwarded to <span class="font-medium text-gray-900">{{ $route->getToName() }}</span>
                                                                by <span class="font-medium text-gray-900">{{ $route->fromUser->name }}</span>
                                                            </p>
                                                        </div>
                                                        <div class="mt-2 text-sm text-gray-700">
                                                            <p>{{ $route->notes }}</p>
                                                        </div>
                                                        <div class="mt-1 text-xs text-gray-500">
                                                            {{ $route->routed_at->diffForHumans() }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Document Viewer -->
                <div class="lg:col-span-2">
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Document Viewer</h3>
                        </div>
                        <div class="p-6">
                            @if($document->status === 'received' || $document->status === 'in_progress' || $document->status === 'completed')
                                <div id="pdf-viewer" data-document-id="{{ $document->id }}" class="relative">
                                    <!-- PDF.js viewer will be loaded here -->
                                    <div class="bg-gray-100 p-8 text-center">
                                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500 mx-auto mb-4"></div>
                                        <p class="text-gray-600">Loading document...</p>
                                    </div>
                                </div>
                            @else
                                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-yellow-800">Document Not Available</h3>
                                            <div class="mt-2 text-sm text-yellow-700">
                                                <p>This document is currently {{ $document->status }} and cannot be viewed.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Minutes Section -->
                    <div class="bg-white shadow rounded-lg mt-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-medium text-gray-900">Minutes & Annotations</h3>
                                @can('create', App\Models\Minute::class)
                                    <button onclick="openMinuteModal()" class="bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 px-4 rounded">
                                        Add Minute
                                    </button>
                                @endcan
                            </div>
                        </div>
                        <div class="p-6">
                            @forelse($minutes as $minute)
                                <div class="border-b border-gray-200 pb-4 mb-4 last:border-b-0" id="minute-{{ $minute->id }}">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <span class="font-medium text-gray-900">{{ $minute->creator->name }}</span>
                                                <span class="text-sm text-gray-500">{{ $minute->created_at->diffForHumans() }}</span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                                    {{ $minute->visibility === 'public' ? 'bg-green-100 text-green-800' : 
                                                       ($minute->visibility === 'department' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                                                    {{ ucfirst($minute->visibility) }}
                                                </span>
                                                @if($minute->hasOverlay())
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                        Page {{ $minute->page_number }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-gray-700">
                                                {{ $minute->body }}
                                            </div>
                                            @if($minute->getForwardedToName())
                                                <div class="mt-2 text-sm text-blue-600">
                                                    Forwarded to {{ $minute->getForwardedToName() }}
                                                </div>
                                            @endif
                                        </div>
                                        @can('update', $minute)
                                            <div class="flex space-x-2">
                                                <button onclick="editMinute({{ $minute->id }})" class="text-blue-600 hover:text-blue-500 text-sm">
                                                    Edit
                                                </button>
                                                <button onclick="deleteMinute({{ $minute->id }})" class="text-red-600 hover:text-red-500 text-sm">
                                                    Delete
                                                </button>
                                            </div>
                                        @endcan
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 text-center py-4">No minutes added yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Minute Modal -->
    <div id="minute-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-medium text-gray-900" id="modal-title">Add Minute</h3>
                </div>
                <form id="minute-form" class="p-6">
                    @csrf
                    <input type="hidden" id="minute-id" name="minute_id">
                    <input type="hidden" id="page-number" name="page_number">
                    <input type="hidden" id="pos-x" name="pos_x">
                    <input type="hidden" id="pos-y" name="pos_y">
                    
                    <div class="space-y-4">
                        <div>
                            <label for="body" class="block text-sm font-medium text-gray-700">Message</label>
                            <textarea id="body" name="body" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"></textarea>
                        </div>

                        <div>
                            <label for="visibility" class="block text-sm font-medium text-gray-700">Visibility</label>
                            <select id="visibility" name="visibility" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                <option value="public">Public</option>
                                <option value="department">Department Only</option>
                                <option value="internal">Internal</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="forwarded_to_type" class="block text-sm font-medium text-gray-700">Forward To</label>
                                <select id="forwarded_to_type" name="forwarded_to_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                    <option value="">Don't Forward</option>
                                    <option value="user">User</option>
                                    <option value="department">Department</option>
                                </select>
                            </div>
                            <div>
                                <label for="forwarded_to_id" class="block text-sm font-medium text-gray-700">Select Recipient</label>
                                <select id="forwarded_to_id" name="forwarded_to_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                    <option value="">Select...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-2">
                        <button type="button" onclick="closeMinuteModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-bold py-2 px-4 rounded">
                            Cancel
                        </button>
                        <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 px-4 rounded">
                            Save Minute
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let users = @json(\App\Models\User::where('is_active', true)->get(['id', 'name']));
        let departments = @json(\App\Models\Department::where('is_active', true)->get(['id', 'name']));
    </script>
    @vite(['resources/js/document-viewer.js'])
</x-app-layout>