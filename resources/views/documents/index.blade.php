@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <div class="flex justify-between items-center mb-6">
            <h2 class="font-bold text-3xl text-gray-900 leading-tight">
                {{ __('Documents') }}
            </h2>

            {{-- @can('create', App\Models\Document::class) --}}
            <button id="open-upload-btn" type="button"
                class="bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 px-4 rounded-lg">
                Upload Document
            </button>
            {{-- @endcan --}}
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4">
                <form method="GET" action="{{ route('documents.index') }}" class="space-y-4" id="filter-form">
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

                        <!-- Status (kept in filters though not shown in table) -->
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
                            <select name="sort" id="sort" onchange="document.getElementById('filter-form').submit()"
                                class="rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                <option value="created_at" {{ request('sort', 'created_at' )==='created_at' ? 'selected'
                                    : '' }}>Created Date</option>
                                <option value="title" {{ request('sort')==='title' ? 'selected' : '' }}>Title</option>
                                <option value="due_date" {{ request('sort')==='due_date' ? 'selected' : '' }}>Due Date
                                </option>
                                <option value="priority" {{ request('sort')==='priority' ? 'selected' : '' }}>Priority
                                </option>
                            </select>
                            <select name="direction" onchange="document.getElementById('filter-form').submit()"
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

        <!-- Documents Table -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="w-full overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <!-- S/N -->
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                S/N
                            </th>

                            <!-- Title -->
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Title
                            </th>

                            <!-- Created By -->
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created By
                            </th>

                            <!-- Assigned To -->
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Assigned To
                            </th>

                            <!-- Created -->
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created
                            </th>

                            <!-- Actions -->
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($documents as $index => $document)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {{-- Calculate absolute serial number across pagination --}}
                                {{ ($documents->currentPage() - 1) * $documents->perPage() + $loop->iteration }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <a href="{{ route('documents.show', $document) }}" class="hover:text-primary-600">
                                        {{ $document->title }}
                                    </a>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-700">{{ $document->creator->name ?? '—' }}</div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-700">
                                    @if($document->getCurrentAssignee())
                                    {{ $document->getCurrentAssignee() }}
                                    @else
                                    —
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {{ $document->created_at ? $document->created_at->format('M j, Y') : '—' }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('documents.show', $document) }}"
                                    class="text-primary-600 hover:text-primary-500 mr-3">
                                    View
                                </a>
                                @can('export', $document)
                                <a href="{{ route('documents.export', $document) }}"
                                    class="text-secondary-600 hover:text-secondary-500">
                                    Export
                                </a>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                <div class="mx-auto max-w-lg">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
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
                                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            Upload Document
                                        </a>
                                    </div>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-100 bg-white">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing <span class="font-medium">{{ $documents->firstItem() ?? 0 }}</span> to <span
                            class="font-medium">{{ $documents->lastItem() ?? 0 }}</span> of <span class="font-medium">{{
                            $documents->total() }}</span> results
                    </div>
                    <div>
                        {{ $documents->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Drawer (hidden by default) -->
<div id="upload-drawer"
    class="fixed inset-y-0 right-0 z-50 w-full sm:w-2/3 md:w-1/2 lg:w-1/3 transform translate-x-full transition-transform duration-300 ease-in-out">
    <div class="h-full flex flex-col bg-white shadow-xl">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Upload New Document</h3>
                <p class="mt-1 text-sm text-gray-600">Fill in the details below to upload and assign your document.</p>
            </div>
            <button id="close-upload-btn" class="text-gray-500 hover:text-gray-700">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="p-6 overflow-y-auto">
            <form id="upload-form" method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data"
                class="space-y-6">
                @csrf

                <!-- Document Information -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Document Title *</label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required
                        placeholder="Enter a descriptive title for your document"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                    <p class="mt-2 text-sm text-red-600 hidden upload-error" data-field="title"></p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="reference_number" class="block text-sm font-medium text-gray-700">Reference
                            Number</label>
                        <input type="text" name="reference_number" id="reference_number"
                            value="{{ old('reference_number') }}" placeholder="e.g., DOC-2024-001"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <p class="mt-2 text-sm text-red-600 hidden upload-error" data-field="reference_number"></p>
                    </div>

                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700">Priority *</label>
                        <select name="priority" id="priority" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Select Priority</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                        <p class="mt-2 text-sm text-red-600 hidden upload-error" data-field="priority"></p>
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('description') }}</textarea>
                    <p class="mt-2 text-sm text-red-600 hidden upload-error" data-field="description"></p>
                </div>

                <!-- File -->
                <div>
                    <label for="file" class="block text-sm font-medium text-gray-700">Document File *</label>
                    <div id="drop-area"
                        class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-gray-400">
                        <div class="space-y-1 text-center w-full">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                viewBox="0 0 48 48">
                                <path
                                    d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex items-center justify-center text-sm text-gray-600">
                                <label for="file"
                                    class="relative cursor-pointer bg-white rounded-md font-medium text-primary-600 hover:text-primary-500">
                                    <span>Upload a file</span>
                                    <input id="file" name="file" type="file" class="sr-only" required
                                        accept=".pdf,.jpg,.jpeg,.png,.tif,.tiff">
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">PDF, JPG, PNG, TIFF up to {{
                                number_format(config('document_workflow.max_upload_size', 52428800) / 1024 / 1024, 0)
                                }}MB</p>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-red-600 hidden upload-error" data-field="file"></p>
                    <div id="file-preview" class="mt-4"></div>
                </div>

                <!-- Assignment -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="assigned_to_type" class="block text-sm font-medium text-gray-700">Assign To
                            *</label>
                        <select name="assigned_to_type" id="assigned_to_type" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Select Assignment Type</option>
                            <option value="user">Specific User</option>
                            <option value="department">Department</option>
                        </select>
                        <p class="mt-2 text-sm text-red-600 hidden upload-error" data-field="assigned_to_type"></p>
                    </div>

                    <div>
                        <label for="assigned_to_id" class="block text-sm font-medium text-gray-700">Select Recipient
                            *</label>
                        <select name="assigned_to_id" id="assigned_to_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Select...</option>
                        </select>
                        <p class="mt-2 text-sm text-red-600 hidden upload-error" data-field="assigned_to_id"></p>
                    </div>
                </div>

                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                    <input type="date" name="due_date" id="due_date"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <p class="mt-2 text-sm text-red-600 hidden upload-error" data-field="due_date"></p>
                </div>

                <div class="flex justify-end items-center space-x-3 pt-4 border-t">
                    <button type="button" id="cancel-upload"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-lg">Cancel</button>
                    <button type="submit" id="submit-upload"
                        class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg">Upload
                        Document</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast container -->
<div id="toast" class="fixed top-6 right-6 z-50 hidden">
    <div class="bg-green-600 text-white px-4 py-2 rounded shadow">
        <span id="toast-message"></span>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
    // Drawer open/close hooks (vanilla)
    const openBtn = document.getElementById('open-upload-btn');
    const closeBtn = document.getElementById('close-upload-btn');
    const cancelBtn = document.getElementById('cancel-upload');
    const drawer = document.getElementById('upload-drawer');

    function openDrawer() {
        drawer.classList.remove('translate-x-full');
    }
    function closeDrawer() {
        drawer.classList.add('translate-x-full');
        // clear errors & preview when closing
        clearFormErrors();
    }

    if (openBtn) openBtn.addEventListener('click', openDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (cancelBtn) cancelBtn.addEventListener('click', closeDrawer);

    // Populate assigned_to_id select based on assigned_to_type
    const assignedToType = document.getElementById('assigned_to_type');
    const assignedToId = document.getElementById('assigned_to_id');

    // const users = @json($users ?? []);
    // const departments = @json($departments ?? []);

    const users = @json($users ?? []);
    const departments = @json($departments ?? []);

    function populateAssignees() {
        assignedToId.innerHTML = '<option value="">Select...</option>';
        if (assignedToType.value === 'user') {
            users.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u.id;
                opt.textContent = u.name;
                assignedToId.appendChild(opt);
            });
        } else if (assignedToType.value === 'department') {
            departments.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = d.name;
                assignedToId.appendChild(opt);
            });
        }
    }

    assignedToType && assignedToType.addEventListener('change', populateAssignees);

    // file preview
    const fileInput = document.querySelector('#upload-form input[name="file"]');
    const filePreview = document.getElementById('file-preview');

    if (fileInput) {
        fileInput.addEventListener('change', function() {
            previewLocalFile(this);
        });
    }

    function previewLocalFile(input) {
        filePreview.innerHTML = '';
        if (!(input.files && input.files[0])) return;
        const file = input.files[0];
        const fileSize = file.size < 1024 * 1024 ? (file.size / 1024).toFixed(1) + ' KB' : (file.size / 1024 / 1024).toFixed(2) + ' MB';
        const blobUrl = URL.createObjectURL(file);

        const container = document.createElement('div');
        container.className = 'p-4 bg-gray-50 border rounded';

        const header = document.createElement('div');
        header.className = 'flex items-center justify-between';
        header.innerHTML = `<div><div class="text-sm font-medium text-gray-900">${file.name}</div><div class="text-xs text-gray-500">${fileSize} • ${file.type || 'Unknown'}</div></div>`;

        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'text-red-600';
        clearBtn.innerHTML = '&times;';
        clearBtn.addEventListener('click', function() {
            document.querySelector('#upload-form input[name="file"]').value = '';
            filePreview.innerHTML = '';
        });

        header.appendChild(clearBtn);
        container.appendChild(header);

        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = blobUrl;
            img.className = 'mt-3 max-h-48 rounded';
            container.appendChild(img);
        } else if (file.type === 'application/pdf') {
            const obj = document.createElement('object');
            obj.data = blobUrl;
            obj.type = 'application/pdf';
            obj.width = '100%';
            obj.height = '300';
            obj.className = 'mt-3 border rounded';
            obj.innerHTML = `<p>Preview not available. <a href="${blobUrl}" target="_blank">Open PDF</a></p>`;
            container.appendChild(obj);
        } else {
            const note = document.createElement('div');
            note.className = 'mt-3 text-sm text-gray-700';
            note.innerHTML = `Preview not available — <a href="${blobUrl}" target="_blank" class="underline">Open file</a>`;
            container.appendChild(note);
        }

        filePreview.appendChild(container);
    }

    // Upload form submit via AJAX
    const uploadForm = document.getElementById('upload-form');
    if (uploadForm) {
        uploadForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            clearFormErrors();
            const submitBtn = document.getElementById('submit-upload');
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-60', 'cursor-not-allowed');

            const formData = new FormData(uploadForm);

            try {
                const response = await fetch(uploadForm.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData,
                });

                if (response.status === 201 || response.status === 200) {
                    const json = await response.json();
                    // close drawer and show toast
                    closeDrawer();
                    showToast(json.message || 'Document uploaded successfully.');
                    // Optionally reload the page to show the new doc in the table
                    setTimeout(() => {
                        location.reload();
                    }, 900); // small delay so toast is visible
                    return;
                }

                if (response.status === 422) {
                    const data = await response.json();
                    if (data.errors) {
                        displayFormErrors(data.errors);
                    } else {
                        showToast('Validation failed. Please check the form.', true);
                    }
                    return;
                }

                // other error
                const text = await response.text();
                showToast('Upload failed. ' + (text || ''), true);
            } catch (err) {
                console.error(err);
                showToast('An unexpected error occurred. Please try again.', true);
            } finally {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            }
        });
    }

    function displayFormErrors(errors) {
        Object.keys(errors).forEach(field => {
            const message = errors[field][0];
            const el = document.querySelector('.upload-error[data-field="' + field + '"]');
            if (el) {
                el.textContent = message;
                el.classList.remove('hidden');
            } else {
                // fallback toast
                showToast(message, true);
            }
        });
    }

    function clearFormErrors() {
        document.querySelectorAll('.upload-error').forEach(el => {
            el.textContent = '';
            el.classList.add('hidden');
        });
    }

    // toast
    const toast = document.getElementById('toast');
    const toastMsg = document.getElementById('toast-message');
    function showToast(message, isError = false) {
        toastMsg.textContent = message;
        toast.firstElementChild.classList.toggle('bg-red-600', !!isError);
        toast.firstElementChild.classList.toggle('bg-green-600', !isError);
        toast.classList.remove('hidden');
        setTimeout(() => {
            toast.classList.add('hidden');
        }, 3500);
    }

@if(session()->has('success'))
            showToast(@json(session('success')), false);
        @endif

        @if(session()->has('error'))
            showToast(@json(session('error')), true);
        @endif

});
</script>

@endsection