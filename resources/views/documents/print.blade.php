@extends('layouts.app')

@section('header')
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Print: {{ $document->title }}
    </h2>
    <div class="flex space-x-2">
        <button onclick="window.print()"
            class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            🖨️ Print Document
        </button>
        <button onclick="window.close()"
            class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            ✕ Close
        </button>
    </div>
</div>
@endsection

@section('content')
<div class="py-8 print:py-4">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 print:px-0">
        <div
            class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200 print:shadow-none print:border-0">
            <!-- Print Header -->
            <div
                class="px-6 py-4 border-b border-gray-200 bg-gray-50 print:bg-white print:border-b-2 print:border-black">
                <div class="text-center">
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $document->title }}</h1>
                    <p class="text-sm text-gray-600">Document with Minutes - Print Version</p>
                </div>
            </div>

            <div class="p-8 print:p-4">
                <!-- Document Details Section -->
                <div class="space-y-6">
                    <div class="border-b border-gray-200 pb-4 print:border-b print:border-black">
                        <h3 class="text-lg font-semibold text-gray-900">Document Details</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 print:gap-4">
                        <div class="space-y-3">
                            <div
                                class="flex justify-between py-2 border-b border-gray-100 print:border-dotted print:border-gray-400">
                                <span class="text-sm font-medium text-gray-700">Status:</span>
                                <span class="text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $document->status))
                                    }}</span>
                            </div>

                            @if($document->reference_number)
                            <div
                                class="flex justify-between py-2 border-b border-gray-100 print:border-dotted print:border-gray-400">
                                <span class="text-sm font-medium text-gray-700">Reference:</span>
                                <span class="text-sm text-gray-900">{{ $document->reference_number }}</span>
                            </div>
                            @endif

                            <div
                                class="flex justify-between py-2 border-b border-gray-100 print:border-dotted print:border-gray-400">
                                <span class="text-sm font-medium text-gray-700">Priority:</span>
                                <span class="text-sm text-gray-900">{{ ucfirst($document->priority) }}</span>
                            </div>

                            <div
                                class="flex justify-between py-2 border-b border-gray-100 print:border-dotted print:border-gray-400">
                                <span class="text-sm font-medium text-gray-700">Created By:</span>
                                <span class="text-sm text-gray-900">{{ $document->creator->name }}</span>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div
                                class="flex justify-between py-2 border-b border-gray-100 print:border-dotted print:border-gray-400">
                                <span class="text-sm font-medium text-gray-700">Created On:</span>
                                <span class="text-sm text-gray-900">{{ $document->created_at->format('M j, Y g:i A')
                                    }}</span>
                            </div>

                            @if($document->getCurrentAssignee())
                            <div
                                class="flex justify-between py-2 border-b border-gray-100 print:border-dotted print:border-gray-400">
                                <span class="text-sm font-medium text-gray-700">Assigned To:</span>
                                <span class="text-sm text-gray-900">{{ $document->getCurrentAssignee() }}</span>
                            </div>
                            @endif

                            @if($document->due_date)
                            <div
                                class="flex justify-between py-2 border-b border-gray-100 print:border-dotted print:border-gray-400">
                                <span class="text-sm font-medium text-gray-700">Due Date:</span>
                                <span class="text-sm text-gray-900">{{ $document->due_date->format('M j, Y') }}</span>
                            </div>
                            @endif

                            <div
                                class="flex justify-between py-2 border-b border-gray-100 print:border-dotted print:border-gray-400">
                                <span class="text-sm font-medium text-gray-700">File Name:</span>
                                <span class="text-sm text-gray-900">{{ $document->file_name }}</span>
                            </div>
                        </div>
                    </div>

                    @if($document->description)
                    <div class="mt-6 print:mt-4">
                        <div class="text-sm font-medium text-gray-700 mb-2">Description:</div>
                        <div class="bg-gray-50 rounded-lg p-4 print:bg-gray-100 print:border print:border-gray-300">
                            <p class="text-sm text-gray-900 leading-relaxed">{{ $document->description }}</p>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Minutes Section -->
                <div class="space-y-6 mt-8 print:mt-6">
                    <div class="border-b border-gray-200 pb-4 print:border-b print:border-black">
                        <h3 class="text-lg font-semibold text-gray-900 text-center print:text-xl">
                            MINUTES & ANNOTATIONS ({{ $minutes->count() }} Total)
                        </h3>
                    </div>

                    @forelse($minutes as $minute)
                    <div
                        class="bg-white border border-gray-200 rounded-lg p-6 mb-4 print:border print:border-gray-400 print:mb-3 print:p-4 print:break-inside-avoid">
                        <div class="flex justify-between items-start mb-4 print:mb-2">
                            <div class="flex-1">
                                <div class="font-medium text-gray-900 print:font-bold">{{ $minute->creator->name }}
                                </div>
                                <div class="text-sm text-gray-500 print:text-xs">{{ $minute->created_at->format('M j, Y
                                    g:i A') }}</div>
                            </div>
                            <div class="text-right">
                                <span
                                    class="inline-block px-2 py-1 text-xs font-medium border border-gray-300 rounded print:border-gray-600">
                                    {{ ucfirst($minute->visibility) }}
                                </span>
                            </div>
                        </div>

                        <div
                            class="bg-gray-50 rounded-lg p-4 mb-3 print:bg-gray-100 print:border print:border-gray-300 print:mb-2">
                            <div class="text-gray-900 leading-relaxed print:text-sm">{{ $minute->body }}</div>
                        </div>

                        @if($minute->hasOverlay())
                        <div class="text-xs text-gray-600 italic print:text-xs">
                            📍 Positioned on page {{ $minute->page_number }} of the document
                        </div>
                        @endif

                        @if($minute->getForwardedToName())
                        <div
                            class="bg-blue-50 border border-blue-200 rounded-lg p-3 mt-2 print:bg-gray-100 print:border-gray-400 print:text-xs">
                            <div class="text-sm text-blue-700 italic print:text-gray-700">
                                ➤ Forwarded to {{ $minute->getForwardedToName() }}
                            </div>
                        </div>
                        @endif
                    </div>
                    @empty
                    <div
                        class="text-center py-12 bg-gray-50 rounded-lg print:py-8 print:bg-gray-100 print:border print:border-gray-300">
                        <p class="text-gray-600 italic">No minutes have been added to this document.</p>
                    </div>
                    @endforelse
                </div>

                <!-- Print Footer -->
                <div
                    class="mt-8 pt-6 border-t border-gray-200 text-center print:mt-6 print:pt-4 print:border-t print:border-gray-400">
                    <p class="text-sm text-gray-500 print:text-xs">Printed on {{ now()->format('M j, Y \a\t g:i A') }}
                    </p>
                    <p class="text-sm text-gray-500 print:text-xs">{{ config('app.name') }} - Document Management System
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body {
            margin: 0;
            padding: 0;
            background: white;
        }

        .print\:py-4 {
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .print\:px-0 {
            padding-left: 0;
            padding-right: 0;
        }

        .print\:p-4 {
            padding: 1rem;
        }

        .print\:mt-4 {
            margin-top: 1rem;
        }

        .print\:mt-6 {
            margin-top: 1.5rem;
        }

        .print\:mb-2 {
            margin-bottom: 0.5rem;
        }

        .print\:mb-3 {
            margin-bottom: 0.75rem;
        }

        .print\:pt-4 {
            padding-top: 1rem;
        }

        .print\:py-8 {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }

        .print\:gap-4 {
            gap: 1rem;
        }

        .print\:shadow-none {
            box-shadow: none;
        }

        .print\:border-0 {
            border-width: 0;
        }

        .print\:border {
            border-width: 1px;
        }

        .print\:border-b {
            border-bottom-width: 1px;
        }

        .print\:border-b-2 {
            border-bottom-width: 2px;
        }

        .print\:border-t {
            border-top-width: 1px;
        }

        .print\:border-black {
            border-color: black;
        }

        .print\:border-gray-300 {
            border-color: #d1d5db;
        }

        .print\:border-gray-400 {
            border-color: #9ca3af;
        }

        .print\:border-gray-600 {
            border-color: #4b5563;
        }

        .print\:border-dotted {
            border-style: dotted;
        }

        .print\:bg-white {
            background-color: white;
        }

        .print\:bg-gray-100 {
            background-color: #f3f4f6;
        }

        .print\:text-xl {
            font-size: 1.25rem;
        }

        .print\:text-sm {
            font-size: 0.875rem;
        }

        .print\:text-xs {
            font-size: 0.75rem;
        }

        .print\:text-gray-700 {
            color: #374151;
        }

        .print\:font-bold {
            font-weight: bold;
        }

        .print\:break-inside-avoid {
            break-inside: avoid;
        }

        /* Hide non-print elements */
        .no-print {
            display: none !important;
        }
    }
</style>

<script>
    // Auto-focus for printing
window.onload = function() {
    // Optional: Auto-print when page loads
    // window.print();
};
</script>
@endsection