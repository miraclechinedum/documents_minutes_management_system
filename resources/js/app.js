import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Global functions for document workflow
window.documentWorkflow = {
    // CSRF token for AJAX requests
    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    },

    // Show notification
    showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg notification-enter ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    },

    // Confirm action
    confirmAction(message, callback) {
        if (confirm(message)) {
            callback();
        }
    },

    // Format file size
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    // Debounce function for search
    debounce(func, wait, immediate) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func(...args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func(...args);
        };
    }
};

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('global-search');
    if (searchInput) {
        const debouncedSearch = window.documentWorkflow.debounce(handleGlobalSearch, 300);
        searchInput.addEventListener('input', debouncedSearch);
    }
});

function handleGlobalSearch(event) {
    const query = event.target.value.trim();
    if (query.length < 2) return;

    fetch(`/api/search?q=${encodeURIComponent(query)}&limit=5`)
        .then(response => response.json())
        .then(data => {
            displaySearchResults(data);
        })
        .catch(error => {
            console.error('Search error:', error);
        });
}

function displaySearchResults(data) {
    // Implementation would depend on UI requirements
    console.log('Search results:', data);
}

// File upload preview
function previewFile(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const preview = document.getElementById('file-preview');
        
        if (preview) {
            preview.innerHTML = `
                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">${file.name}</p>
                        <p class="text-sm text-gray-500">${window.documentWorkflow.formatFileSize(file.size)}</p>
                    </div>
                </div>
            `;
        }
    }
}

// Make functions globally available
window.previewFile = previewFile;