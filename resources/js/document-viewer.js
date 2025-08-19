// PDF.js Document Viewer with Annotation Support
import * as pdfjsLib from 'pdfjs-dist';

// Set worker source
pdfjsLib.GlobalWorkerOptions.workerSrc = new URL('pdfjs-dist/build/pdf.worker.js', import.meta.url);

class DocumentViewer {
    constructor(containerId, documentId) {
        this.container = document.getElementById(containerId);
        this.documentId = documentId;
        this.pdfDoc = null;
        this.pageRendering = false;
        this.pageNumPending = null;
        this.scale = 1.2;
        this.pages = [];
        this.minutes = [];
        this.selectedMinute = null;
        
        this.init();
    }

    async init() {
        try {
            // Load PDF
            const pdfUrl = `/documents/${this.documentId}/download`;
            this.pdfDoc = await pdfjsLib.getDocument(pdfUrl).promise;
            
            // Clear loading message
            this.container.innerHTML = '';
            
            // Create viewer container
            const viewerContainer = document.createElement('div');
            viewerContainer.className = 'pdf-viewer-container';
            this.container.appendChild(viewerContainer);
            
            // Render all pages
            for (let pageNum = 1; pageNum <= this.pdfDoc.numPages; pageNum++) {
                await this.renderPage(pageNum, viewerContainer);
            }
            
            // Load existing minutes
            this.loadMinutes();
            
        } catch (error) {
            console.error('Error loading PDF:', error);
            this.showError('Failed to load document. Please try again.');
        }
    }

    async renderPage(pageNum, container) {
        try {
            const page = await this.pdfDoc.getPage(pageNum);
            const viewport = page.getViewport({ scale: this.scale });
            
            // Create page container
            const pageContainer = document.createElement('div');
            pageContainer.className = 'pdf-page relative';
            pageContainer.setAttribute('data-page', pageNum);
            
            // Create canvas
            const canvas = document.createElement('canvas');
            canvas.className = 'pdf-canvas';
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            
            const context = canvas.getContext('2d');
            
            // Render page
            const renderContext = {
                canvasContext: context,
                viewport: viewport
            };
            
            await page.render(renderContext).promise;
            
            // Add click handler for annotation placement
            canvas.addEventListener('click', (event) => {
                this.handleCanvasClick(event, pageNum, viewport);
            });
            
            pageContainer.appendChild(canvas);
            container.appendChild(pageContainer);
            
            // Store page info
            this.pages[pageNum] = {
                container: pageContainer,
                canvas: canvas,
                viewport: viewport
            };
            
        } catch (error) {
            console.error(`Error rendering page ${pageNum}:`, error);
        }
    }

    handleCanvasClick(event, pageNum, viewport) {
        const rect = event.target.getBoundingClientRect();
        const x = (event.clientX - rect.left) / viewport.width;
        const y = (event.clientY - rect.top) / viewport.height;
        
        // Open minute modal with coordinates
        this.openMinuteModal(pageNum, x, y);
    }

    openMinuteModal(pageNumber = null, posX = null, posY = null) {
        const modal = document.getElementById('minute-modal');
        const form = document.getElementById('minute-form');
        const title = document.getElementById('modal-title');
        
        // Reset form
        form.reset();
        document.getElementById('minute-id').value = '';
        
        // Set coordinates if provided
        if (pageNumber !== null) {
            document.getElementById('page-number').value = pageNumber;
            document.getElementById('pos-x').value = posX.toFixed(4);
            document.getElementById('pos-y').value = posY.toFixed(4);
            title.textContent = `Add Minute (Page ${pageNumber})`;
        } else {
            title.textContent = 'Add Minute';
        }
        
        modal.classList.remove('hidden');
    }

    loadMinutes() {
        // This would be populated from the server-side data
        // For now, we'll use the global minutes data if available
        if (window.minutesData) {
            this.minutes = window.minutesData;
            this.renderMinuteMarkers();
        }
    }

    renderMinuteMarkers() {
        this.minutes.forEach(minute => {
            if (minute.page_number && minute.pos_x !== null && minute.pos_y !== null) {
                this.addMinuteMarker(minute);
            }
        });
    }

    addMinuteMarker(minute) {
        const pageInfo = this.pages[minute.page_number];
        if (!pageInfo) return;
        
        const marker = document.createElement('div');
        marker.className = 'minute-marker';
        marker.setAttribute('data-minute-id', minute.id);
        marker.style.left = `${minute.pos_x * pageInfo.viewport.width}px`;
        marker.style.top = `${minute.pos_y * pageInfo.viewport.height}px`;
        
        // Add tooltip
        marker.title = minute.body.substring(0, 100) + (minute.body.length > 100 ? '...' : '');
        
        // Add click handler
        marker.addEventListener('click', (event) => {
            event.stopPropagation();
            this.selectMinute(minute.id);
            // Scroll to minute in the list
            const minuteElement = document.getElementById(`minute-${minute.id}`);
            if (minuteElement) {
                minuteElement.scrollIntoView({ behavior: 'smooth' });
                minuteElement.classList.add('bg-yellow-50');
                setTimeout(() => {
                    minuteElement.classList.remove('bg-yellow-50');
                }, 2000);
            }
        });
        
        pageInfo.container.appendChild(marker);
    }

    selectMinute(minuteId) {
        // Deselect previous
        if (this.selectedMinute) {
            const prevMarker = document.querySelector(`[data-minute-id="${this.selectedMinute}"]`);
            if (prevMarker) {
                prevMarker.classList.remove('selected');
            }
        }
        
        // Select new
        this.selectedMinute = minuteId;
        const marker = document.querySelector(`[data-minute-id="${minuteId}"]`);
        if (marker) {
            marker.classList.add('selected');
        }
    }

    showError(message) {
        this.container.innerHTML = `
            <div class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Error</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>${message}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
}

// Initialize viewer when page loads
document.addEventListener('DOMContentLoaded', function() {
    const viewerElement = document.getElementById('pdf-viewer');
    if (viewerElement) {
        const documentId = viewerElement.getAttribute('data-document-id');
        window.documentViewer = new DocumentViewer('pdf-viewer', documentId);
    }
});

// Global functions for minute management
window.openMinuteModal = function(pageNumber = null, posX = null, posY = null) {
    if (window.documentViewer) {
        window.documentViewer.openMinuteModal(pageNumber, posX, posY);
    } else {
        // Fallback for non-PDF documents
        const modal = document.getElementById('minute-modal');
        const title = document.getElementById('modal-title');
        title.textContent = 'Add Minute';
        modal.classList.remove('hidden');
    }
};

window.closeMinuteModal = function() {
    const modal = document.getElementById('minute-modal');
    modal.classList.add('hidden');
    document.getElementById('minute-form').reset();
};

window.editMinute = function(minuteId) {
    // Implementation for editing minutes
    console.log('Edit minute:', minuteId);
};

window.deleteMinute = function(minuteId) {
    if (confirm('Are you sure you want to delete this minute?')) {
        fetch(`/minutes/${minuteId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting minute');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting minute');
        });
    }
};

// Handle minute form submission
document.addEventListener('DOMContentLoaded', function() {
    const minuteForm = document.getElementById('minute-form');
    if (minuteForm) {
        minuteForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const formData = new FormData(this);
            const documentId = document.getElementById('pdf-viewer')?.getAttribute('data-document-id') || 
                            window.location.pathname.split('/').pop();
            
            fetch(`/documents/${documentId}/minutes`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else if (data.errors) {
                    // Handle validation errors
                    console.error('Validation errors:', data.errors);
                    alert('Please check your input and try again.');
                } else {
                    alert('Error saving minute');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving minute');
            });
        });
    }

    // Handle forwarding dropdowns
    const forwardedToType = document.getElementById('forwarded_to_type');
    const forwardedToId = document.getElementById('forwarded_to_id');
    
    if (forwardedToType && forwardedToId) {
        forwardedToType.addEventListener('change', function() {
            const type = this.value;
            forwardedToId.innerHTML = '<option value="">Select...</option>';
            
            if (type === 'user' && window.users) {
                window.users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.name;
                    forwardedToId.appendChild(option);
                });
            } else if (type === 'department' && window.departments) {
                window.departments.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.id;
                    option.textContent = dept.name;
                    forwardedToId.appendChild(option);
                });
            }
        });
    }
});