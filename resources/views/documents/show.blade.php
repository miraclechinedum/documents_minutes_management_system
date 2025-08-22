@extends('layouts.app')

{{-- @section('header') --}}
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ $document->title }}
    </h2>

    {{-- Debug: Check if user has permissions --}}
    <div class="text-sm text-gray-500">
        Can update: {{ Auth::user()->can('update', $document) ? 'Yes' : 'No' }} |
        Can export: {{ Auth::user()->can('export', $document) ? 'Yes' : 'No' }}
    </div>

    <div class="flex flex-wrap gap-2">
        @can('update', $document)
        <a href="{{ route('documents.edit', $document) }}"
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            Edit
        </a>
        @endcan
        @can('export', $document)
        <a href="{{ route('documents.export', $document) }}"
            class="bg-secondary-600 hover:bg-secondary-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            Export PDF
        </a>
        @endcan
        <button onclick="printDocument()"
            class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            Print Document
        </button>
        <a href="{{ route('documents.download', $document) }}"
            class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            Download Original
        </a>
    </div>
</div>
{{-- @endsection --}}

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Document Details Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Document Information Card -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">Document Details</h3>
                        <p class="mt-1 text-sm text-gray-600">Basic information about this document.</p>
                    </div>
                    <div class="p-6">
                        <dl class="space-y-4">
                            <div class="flex justify-between items-start">
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd>
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $document->status_color ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst(str_replace('_', ' ', $document->status)) }}
                                    </span>
                                </dd>
                            </div>

                            @if($document->reference_number)
                            <div class="flex justify-between items-start">
                                <dt class="text-sm font-medium text-gray-500">Reference</dt>
                                <dd class="text-sm text-gray-900 font-mono">{{ $document->reference_number }}</dd>
                            </div>
                            @endif

                            <div class="flex justify-between items-start">
                                <dt class="text-sm font-medium text-gray-500">Priority</dt>
                                <dd>
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $document->priority_color ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($document->priority) }}
                                    </span>
                                </dd>
                            </div>

                            <div class="flex justify-between items-start">
                                <dt class="text-sm font-medium text-gray-500">Created By</dt>
                                <dd class="text-sm text-gray-900">{{ $document->creator->name }}</dd>
                            </div>

                            <div class="flex justify-between items-start">
                                <dt class="text-sm font-medium text-gray-500">Created On</dt>
                                <dd class="text-sm text-gray-900">{{ $document->created_at->format('M j, Y \a\t g:i A')
                                    }}</dd>
                            </div>

                            @if($document->getCurrentAssignee())
                            <div class="flex justify-between items-start">
                                <dt class="text-sm font-medium text-gray-500">Assigned To</dt>
                                <dd class="text-sm text-gray-900">{{ $document->getCurrentAssignee() }}</dd>
                            </div>
                            @endif

                            @if($document->due_date)
                            <div class="flex justify-between items-start">
                                <dt class="text-sm font-medium text-gray-500">Due Date</dt>
                                <dd class="text-sm text-red-600 font-medium">{{ $document->due_date->format('M j, Y') }}
                                </dd>
                            </div>
                            @endif

                            <div class="pt-4 border-t border-gray-200">
                                <dt class="text-sm font-medium text-gray-500 mb-2">File Information</dt>
                                <dd class="text-sm text-gray-900 space-y-1">
                                    <div class="flex justify-between">
                                        <span>Filename:</span>
                                        <span class="font-mono text-xs">{{ $document->file_name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Size:</span>
                                        <span>{{ number_format($document->file_size / 1024, 1) }} KB</span>
                                    </div>
                                    @if($document->pages)
                                    <div class="flex justify-between">
                                        <span>Pages:</span>
                                        <span>{{ $document->pages }}</span>
                                    </div>
                                    @endif
                                </dd>
                            </div>

                            @if($document->description)
                            <div class="pt-4 border-t border-gray-200">
                                <dt class="text-sm font-medium text-gray-500 mb-2">Description</dt>
                                <dd class="text-sm text-gray-900 leading-relaxed">{{ $document->description }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <!-- Routing History -->
                {{-- @if($document->routes->count() > 0) --}}
                <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">Routing History</h3>
                        <p class="mt-1 text-sm text-gray-600">Document forwarding and routing history.</p>
                    </div>
                    <div class="p-6">
                        <div class="flow-root">
                            <ul class="-mb-8">
                                @foreach($document->routes as $route)
                                <li>
                                    <div class="relative pb-8">
                                        @if(!$loop->last)
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                                            aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span
                                                    class="h-8 w-8 rounded-full bg-primary-500 flex items-center justify-center ring-8 ring-white">
                                                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M13 7l5 5-5 5M6 12h12"></path>
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5">
                                                <div>
                                                    <p class="text-sm text-gray-500">
                                                        Forwarded to <span class="font-medium text-gray-900">{{
                                                            $route->getToName() }}</span>
                                                        by <span class="font-medium text-gray-900">{{
                                                            $route->fromUser->name }}</span>
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
                </div>
                {{-- @endif --}}
            </div>

            <!-- Main Content Area -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Document Viewer -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">Document Viewer</h3>
                        <p class="mt-1 text-sm text-gray-600">View and interact with the document content.</p>
                    </div>
                    <div class="p-6">
                        @if($document->status === 'received' || $document->status === 'in_progress' || $document->status
                        === 'completed')
                        <div id="pdf-viewer" data-document-id="{{ $document->id }}" class="relative min-h-96">
                            <!-- PDF.js viewer will be loaded here -->
                            <div class="bg-gray-100 p-12 text-center rounded-lg">
                                <div
                                    class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500 mx-auto mb-4">
                                </div>
                                <p class="text-gray-600 text-lg">Loading document...</p>
                                <p class="text-gray-500 text-sm mt-2">Please wait while we prepare your document for
                                    viewing</p>
                            </div>
                        </div>
                        @else
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Document Not Available</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>This document is currently <strong>{{ $document->status }}</strong> and
                                            cannot be viewed at this time.</p>
                                        @if($document->status === 'quarantined')
                                        <p class="mt-1">The document is being scanned for security. This usually takes a
                                            few moments.</p>
                                        @elseif($document->status === 'scanning')
                                        <p class="mt-1">Security scan in progress. Please refresh the page in a moment.
                                        </p>
                                        @elseif($document->status === 'infected')
                                        <p class="mt-1">This document has been quarantined due to security concerns.
                                            Please contact your administrator.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Minutes Section -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Minutes & Annotations</h3>
                                <p class="mt-1 text-sm text-gray-600">Comments and notes associated with this document.
                                </p>
                            </div>
                            @can('create', App\Models\Minute::class)
                            <button onclick="openMinuteModal()"
                                class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Minute
                            </button>
                            @endcan
                        </div>
                    </div>
                    <div class="p-6">
                        @forelse($minutes as $minute)
                        <div class="border-b border-gray-200 pb-6 mb-6 last:border-b-0 last:pb-0 last:mb-0"
                            id="minute-{{ $minute->id }}">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-3">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                                <span class="text-sm font-medium text-gray-700">{{
                                                    substr($minute->creator->name, 0, 1) }}</span>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <span class="font-medium text-gray-900">{{ $minute->creator->name }}</span>
                                            <span class="text-sm text-gray-500 ml-2">{{
                                                $minute->created_at->diffForHumans() }}</span>
                                        </div>
                                        <div class="flex space-x-2">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                    {{ $minute->visibility === 'public' ? 'bg-green-100 text-green-800' : 
                                                       ($minute->visibility === 'department' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                                                {{ ucfirst($minute->visibility) }}
                                            </span>
                                            @if($minute->hasOverlay())
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                Page {{ $minute->page_number }}
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4 mb-3">
                                        <div class="text-gray-900 leading-relaxed">{{ $minute->body }}</div>
                                    </div>
                                    @if($minute->getForwardedToName())
                                    <div class="flex items-center text-sm text-blue-600 bg-blue-50 rounded-lg p-3">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 7l5 5-5 5M6 12h12"></path>
                                        </svg>
                                        Forwarded to {{ $minute->getForwardedToName() }}
                                    </div>
                                    @endif
                                </div>
                                @can('update', $minute)
                                <div class="flex space-x-2 ml-4">
                                    <button onclick="editMinute({{ $minute->id }})"
                                        class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                                        Edit
                                    </button>
                                    <button onclick="deleteMinute({{ $minute->id }})"
                                        class="text-red-600 hover:text-red-500 text-sm font-medium">
                                        Delete
                                    </button>
                                </div>
                                @endcan
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                                </path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No minutes added yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by adding the first minute to this
                                document.</p>
                            @can('create', App\Models\Minute::class)
                            <div class="mt-6">
                                <button onclick="openMinuteModal()"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                                    Add First Minute
                                </button>
                            </div>
                            @endcan
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Minute Modal -->
<div id="minute-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-900" id="modal-title">Add Minute</h3>
                <p class="mt-1 text-sm text-gray-600">Add a comment or note to this document.</p>
            </div>
            <form id="minute-form" class="p-6">
                @csrf
                <input type="hidden" id="minute-id" name="minute_id">
                <input type="hidden" id="page-number" name="page_number">
                <input type="hidden" id="pos-x" name="pos_x">
                <input type="hidden" id="pos-y" name="pos_y">

                <div class="space-y-4">
                    <div>
                        <label for="body" class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
                        <textarea id="body" name="body" rows="4" required
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                            placeholder="Enter your minute or comment..."></textarea>
                    </div>

                    <div>
                        <label for="visibility" class="block text-sm font-medium text-gray-700 mb-2">Visibility
                            *</label>
                        <select id="visibility" name="visibility" required
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                            <option value="public">Public - Visible to all who can view document</option>
                            <option value="department">Department Only - Visible to department members</option>
                            <option value="internal">Internal - Visible only to you and admins</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="forwarded_to_type" class="block text-sm font-medium text-gray-700 mb-2">Forward
                                To</label>
                            <select id="forwarded_to_type" name="forwarded_to_type"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                <option value="">Don't Forward</option>
                                <option value="user">Specific User</option>
                                <option value="department">Department</option>
                            </select>
                        </div>
                        <div>
                            <label for="forwarded_to_id" class="block text-sm font-medium text-gray-700 mb-2">Select
                                Recipient</label>
                            <select id="forwarded_to_id" name="forwarded_to_id"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                <option value="">Select...</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3">
                    <button type="button" onclick="closeMinuteModal()"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
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

    function printDocument() {
        window.open('{{ route("documents.print", $document) }}', '_blank', 'width=800,height=600');
    }

    // Handle forwarding type change
    document.addEventListener('DOMContentLoaded', function() {
        const forwardedToType = document.getElementById('forwarded_to_type');
        const forwardedToId = document.getElementById('forwarded_to_id');
        
        forwardedToType.addEventListener('change', function() {
            const type = this.value;
            
            // Clear existing options
            forwardedToId.innerHTML = '<option value="">Select...</option>';
            
            if (type === 'user') {
                // Populate with users
                users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.name;
                    forwardedToId.appendChild(option);
                });
            } else if (type === 'department') {
                // Populate with departments
                departments.forEach(department => {
                    const option = document.createElement('option');
                    option.value = department.id;
                    option.textContent = department.name;
                    forwardedToId.appendChild(option);
                });
            }
        });
    });

    // Modal functions
    function openMinuteModal() {
        document.getElementById('minute-modal').classList.remove('hidden');
        document.getElementById('modal-title').textContent = 'Add Minute';
        document.getElementById('minute-form').reset();
        document.getElementById('minute-id').value = '';
    }

    function closeMinuteModal() {
        document.getElementById('minute-modal').classList.add('hidden');
    }

    function editMinute(minuteId) {
        // Implementation for editing minute
        console.log('Edit minute:', minuteId);
    }

    function deleteMinute(minuteId) {
        if (confirm('Are you sure you want to delete this minute?')) {
            // Implementation for deleting minute
            console.log('Delete minute:', minuteId);
        }
    }

    // Handle form submission
    document.getElementById('minute-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const minuteId = document.getElementById('minute-id').value;
        
        const url = minuteId ? 
            `/minutes/${minuteId}` : 
            `/documents/{{ $document->id }}/minutes`;
        
        const method = minuteId ? 'PUT' : 'POST';
        
        fetch(url, {
            method: method,
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeMinuteModal();
                location.reload(); // Refresh to show new minute
            } else {
                alert('Error saving minute');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving minute');
        });
    });
</script>
@vite(['resources/js/document-viewer.js'])
@endsection