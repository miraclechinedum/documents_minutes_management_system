@extends('layouts.app')

<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ $document->title }}
    </h2>

    <div class="text-sm text-gray-500">
        Can update: {{ Auth::user()->can('update', $document) ? 'Yes' : 'No' }} |
        Can export: {{ Auth::user()->can('export', $document) ? 'Yes' : 'No' }}
    </div>

    <div class="flex flex-wrap gap-2">
        @can('update', $document)
        <a href="{{ route('documents.edit', $document) }}"
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">Edit</a>
        @endcan

        @can('export', $document)
        <a href="{{ route('documents.export', $document) }}?mode=overlay"
            class="bg-secondary-600 hover:bg-secondary-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">Export
            PDF</a>
        @endcan

        <button onclick="printDocument()"
            class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">Print
            Document</button>

        <a href="{{ route('documents.download', $document) }}"
            class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">Download
            Original</a>
    </div>
</div>

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 space-y-6">
                <!-- Document Details -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">Document Details</h3>
                        <p class="mt-1 text-sm text-gray-600">Basic information about this document.</p>
                    </div>
                    <div class="p-6">
                        <dl class="space-y-4">
                            <div class="flex justify-between items-start">
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd><span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $document->status_color ?? 'bg-gray-100 text-gray-800' }}">{{
                                        ucfirst(str_replace('_',' ',$document->status)) }}</span></dd>
                            </div>

                            @if($document->reference_number)
                            <div class="flex justify-between items-start">
                                <dt class="text-sm font-medium text-gray-500">Reference</dt>
                                <dd class="text-sm text-gray-900 font-mono">{{ $document->reference_number }}</dd>
                            </div>
                            @endif

                            <div class="flex justify-between items-start">
                                <dt class="text-sm font-medium text-gray-500">Priority</dt>
                                <dd><span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $document->priority_color ?? 'bg-gray-100 text-gray-800' }}">{{
                                        ucfirst($document->priority) }}</span></dd>
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
                                    <div class="flex justify-between"><span>Filename:</span><span
                                            class="font-mono text-xs">{{ $document->file_name }}</span></div>
                                    <div class="flex justify-between"><span>Size:</span><span>{{
                                            number_format($document->file_size / 1024, 1) }} KB</span></div>
                                    @if($document->pages)<div class="flex justify-between"><span>Pages:</span><span>{{
                                            $document->pages }}</span></div>@endif
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
                                        @if(!$loop->last)<span
                                            class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                                            aria-hidden="true"></span>@endif
                                        <div class="relative flex space-x-3">
                                            <div><span
                                                    class="h-8 w-8 rounded-full bg-primary-500 flex items-center justify-center ring-8 ring-white"><svg
                                                        class="h-4 w-4 text-white" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M13 7l5 5-5 5M6 12h12"></path>
                                                    </svg></span></div>
                                            <div class="min-w-0 flex-1 pt-1.5">
                                                <div>
                                                    <p class="text-sm text-gray-500">Forwarded to <span
                                                            class="font-medium text-gray-900">{{ $route->getToName()
                                                            }}</span> by <span class="font-medium text-gray-900">{{
                                                            $route->fromUser->name }}</span></p>
                                                </div>
                                                <div class="mt-2 text-sm text-gray-700">
                                                    <p>{{ $route->notes }}</p>
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500">{{
                                                    $route->routed_at->diffForHumans() }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main viewer area only (minutes list & modal removed) -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">Document Viewer</h3>
                        <p class="mt-1 text-sm text-gray-600">View and interact with the document content.</p>
                    </div>
                    <div class="p-6">
                        @if(in_array($document->status, ['received','in_progress','completed']))
                        <div id="pdf-viewer" class="relative">
                            <div id="pdf-loading" class="bg-gray-100 p-12 text-center rounded-lg">
                                <div
                                    class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500 mx-auto mb-4">
                                </div>
                                <p class="text-gray-600 text-lg">Loading document...</p>
                                <p class="text-gray-500 text-sm mt-2">Please wait while we prepare your document for
                                    viewing</p>
                            </div>

                            <div id="pdf-scroll-container" class="overflow-y-auto max-h-[calc(100vh-220px)]">
                                <div id="pdf-container" class="px-4 sm:px-6 lg:px-8 space-y-6"></div>
                            </div>
                        </div>
                        @else
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                            <div class="flex">
                                <div class="flex-shrink-0"><svg class="h-6 w-6 text-yellow-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                                        </path>
                                    </svg></div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Document Not Available</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>This document is currently <strong>{{ $document->status }}</strong> and
                                            cannot be viewed at this time.</p>
                                        @if($document->status === 'quarantined')<p class="mt-1">The document is being
                                            scanned for security. This usually takes a few moments.</p>
                                        @elseif($document->status === 'scanning')<p class="mt-1">Security scan in
                                            progress. Please refresh the page in a moment.</p>
                                        @elseif($document->status === 'infected')<p class="mt-1">This document has been
                                            quarantined due to security concerns. Please contact your administrator.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    window.DOC = {
        id: {{ $document->id }},
        previewUrl: @json(route('documents.preview', $document)),
        csrf: @json(csrf_token()),
        canCreateMinute: @json(Auth::user()->can('create', App\Models\Minute::class)),
        minutes: @json($minutes),
        users: @json(\App\Models\User::where('is_active', true)->get(['id','name'])),
        departments: @json(\App\Models\Department::where('is_active', true)->get(['id','name']))
    };

    function printDocument() {
        window.open('{{ route("documents.print", $document) }}', '_blank', 'width=800,height=600');
    }
</script>


@vite(['resources/js/document-viewer.js'])
@endsection