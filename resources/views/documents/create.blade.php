@extends('layouts.app')

@section('header')
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Upload Document') }}
    </h2>
    <a href="{{ route('documents.index') }}"
        class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 text-center">
        Back to Documents
    </a>
</div>
@endsection

@section('content')
<div class="py-8">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-900">Upload New Document</h3>
                <p class="mt-1 text-sm text-gray-600">Fill in the details below to upload and assign your document.</p>
            </div>
            <div class="p-8">
                <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data"
                    class="space-y-8">
                    @csrf

                    <!-- Document Information Section -->
                    <div class="space-y-6">
                        <div class="border-b border-gray-200 pb-4">
                            <h4 class="text-lg font-medium text-gray-900">Document Information</h4>
                            <p class="mt-1 text-sm text-gray-600">Basic information about the document you're uploading.
                            </p>
                        </div>

                        <!-- Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Document Title
                                *</label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}" required
                                placeholder="Enter a descriptive title for your document"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50 @error('title') border-red-300 @enderror">
                            @error('title')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Reference Number and Description Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Reference Number -->
                            <div>
                                <label for="reference_number"
                                    class="block text-sm font-medium text-gray-700 mb-2">Reference Number</label>
                                <input type="text" name="reference_number" id="reference_number"
                                    value="{{ old('reference_number') }}" placeholder="e.g., DOC-2024-001"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50 @error('reference_number') border-red-300 @enderror">
                                @error('reference_number')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Priority -->
                            <div>
                                <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">Priority
                                    *</label>
                                <select name="priority" id="priority" required
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50 @error('priority') border-red-300 @enderror">
                                    <option value="">Select Priority</option>
                                    <option value="low" {{ old('priority')==='low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('priority')==='medium' ? 'selected' : '' }}>Medium
                                    </option>
                                    <option value="high" {{ old('priority')==='high' ? 'selected' : '' }}>High</option>
                                </select>
                                @error('priority')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description"
                                class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" id="description" rows="3"
                                placeholder="Provide additional details about this document..."
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50 @error('description') border-red-300 @enderror">{{ old('description') }}</textarea>
                            @error('description')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- File Upload Section -->
                    <div class="space-y-6">
                        <div class="border-b border-gray-200 pb-4">
                            <h4 class="text-lg font-medium text-gray-900">File Upload</h4>
                            <p class="mt-1 text-sm text-gray-600">Select the document file to upload. Supported formats:
                                PDF, JPG, PNG, TIFF.</p>
                        </div>

                        <!-- File Upload -->
                        <div>
                            <label for="file" class="block text-sm font-medium text-gray-700 mb-3">Document File
                                *</label>
                            <div
                                class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-gray-400 transition-colors duration-200">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                        viewBox="0 0 48 48">
                                        <path
                                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="file"
                                            class="relative cursor-pointer bg-white rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                                            <span>Upload a file</span>
                                            <input id="file" name="file" type="file" class="sr-only" required
                                                accept=".pdf,.jpg,.jpeg,.png,.tif,.tiff" onchange="previewFile(this)">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PDF, JPG, PNG, TIFF up to {{
                                        number_format(config('document_workflow.max_upload_size', 52428800) / 1024 /
                                        1024, 0) }}MB</p>
                                </div>
                            </div>
                            @error('file')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            <!-- File Preview -->
                            <div id="file-preview" class="mt-4 hidden"></div>
                        </div>
                    </div>

                    <!-- Assignment Section -->
                    <div class="space-y-6">
                        <div class="border-b border-gray-200 pb-4">
                            <h4 class="text-lg font-medium text-gray-900">Document Assignment</h4>
                            <p class="mt-1 text-sm text-gray-600">Assign this document to a specific user or department
                                for processing.</p>
                        </div>

                        <!-- Assignment -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="assigned_to_type"
                                    class="block text-sm font-medium text-gray-700 mb-2">Assign To *</label>
                                <select name="assigned_to_type" id="assigned_to_type" required
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50 @error('assigned_to_type') border-red-300 @enderror">
                                    <option value="">Select Assignment Type</option>
                                    <option value="user" {{ old('assigned_to_type')==='user' ? 'selected' : '' }}>
                                        Specific User</option>
                                    <option value="department" {{ old('assigned_to_type')==='department' ? 'selected'
                                        : '' }}>Department</option>
                                </select>
                                @error('assigned_to_type')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="assigned_to_id" class="block text-sm font-medium text-gray-700 mb-2">Select
                                    Recipient *</label>
                                <select name="assigned_to_id" id="assigned_to_id" required
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50 @error('assigned_to_id') border-red-300 @enderror">
                                    <option value="">Select...</option>
                                </select>
                                @error('assigned_to_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Due Date -->
                        <div>
                            <label for="due_date" class="block text-sm font-medium text-gray-700 mb-2">Due Date</label>
                            <input type="date" name="due_date" id="due_date" value="{{ old('due_date') }}"
                                min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                class="block w-full md:w-1/2 rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50 @error('due_date') border-red-300 @enderror">
                            <p class="mt-1 text-xs text-gray-500">Optional: Set a deadline for this document</p>
                            @error('due_date')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div
                        class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3 pt-8 border-t border-gray-200">
                        <a href="{{ route('documents.index') }}"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-3 px-6 rounded-lg transition-colors duration-200 text-center">
                            Cancel
                        </a>
                        <button type="submit"
                            class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-3 px-6 rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                </path>
                            </svg>
                            Upload Document
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Handle assignment type change
document.addEventListener('DOMContentLoaded', function() {
    const assignedToType = document.getElementById('assigned_to_type');
    const assignedToId = document.getElementById('assigned_to_id');
    
    assignedToType.addEventListener('change', function() {
    const type = this.value;
    
    // Clear existing options
    assignedToId.innerHTML = '<option value="">Select...</option>';
    
    if (type === 'user') {
        // Populate with users
        const users = @json($users ?? []);
        users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = user.name;
            if ('{{ old('assigned_to_id') }}' == user.id) {
                option.selected = true;
            }
            assignedToId.appendChild(option);
        });
    } else if (type === 'department') {
        // Populate with departments
        const departments = @json($departments ?? []);
        departments.forEach(department => {
            const option = document.createElement('option');
            option.value = department.id;
            option.textContent = department.name;
            if ('{{ old('assigned_to_id') }}' == department.id) {
                option.selected = true;
            }
            assignedToId.appendChild(option);
        });
    }
    });

    // Trigger change event on page load if there's an old value
    if (assignedToType.value) {
        assignedToType.dispatchEvent(new Event('change'));
    }
});

// File preview function
function previewFile(input) {
    const preview = document.getElementById('file-preview');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileSize = file.size < 1024 * 1024 ? 
            (file.size / 1024).toFixed(1) + ' KB' : 
            (file.size / 1024 / 1024).toFixed(2) + ' MB';
        
        preview.innerHTML = `
            <div class="flex items-center space-x-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex-shrink-0">
                    <svg class="h-10 w-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-green-900 truncate">${file.name}</p>
                    <p class="text-sm text-green-700">${fileSize} • ${file.type}</p>
                </div>
                <div class="flex-shrink-0">
                    <button type="button" onclick="clearFile()" class="text-red-600 hover:text-red-500 p-1">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `;
        preview.classList.remove('hidden');
    }
}

function clearFile() {
    document.getElementById('file').value = '';
    const preview = document.getElementById('file-preview');
    preview.classList.add('hidden');
    preview.innerHTML = '';
}
</script>
@endsection