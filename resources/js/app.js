// resources/js/app.js
import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Dynamic form handling
document.addEventListener('DOMContentLoaded', function() {
    // Survey Visit Type Change Handler
    const visitTypeSelect = document.getElementById('visit_type');
    if (visitTypeSelect) {
        visitTypeSelect.addEventListener('change', function() {
            updateAreasBasedOnVisitType(this.value);
        });
    }
    
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize search functionality
    initializeSearch();
});

function updateAreasBasedOnVisitType(visitType) {
    // Dynamic area loading based on visit type
    const areaSelect = document.getElementById('area_template');
    if (!areaSelect) return;
    
    // Clear existing options
    areaSelect.innerHTML = '<option value="">Select an area template...</option>';
    
    // Define areas based on visit type
    const areaTemplates = {
        'PRELIMINARY': [
            'Area I: VMGO',
            'Area II: Faculty',
            'Area III: Curriculum and Instruction',
            'Area IV: Support to Students',
            'Area V: Research',
            'Area VI: Extension and Community Involvement',
            'Area VII: Library',
            'Area VIII: Physical Plant and Facilities',
            'Area IX: Laboratories',
            'Area X: Administration'
        ],
        'FIRST': [
            'Area I: VMGO',
            'Area II: Faculty',
            // ... other areas
        ]
    };
    
    const templates = areaTemplates[visitType] || [];
    templates.forEach(template => {
        const option = document.createElement('option');
        option.value = template;
        option.textContent = template;
        areaSelect.appendChild(option);
    });
}

function initializeTooltips() {
    // Initialize tooltips for better UX
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(event) {
    const tooltip = document.createElement('div');
    tooltip.className = 'absolute z-50 px-3 py-2 text-sm text-white bg-gray-900 rounded-lg shadow-lg';
    tooltip.textContent = event.target.dataset.tooltip;
    document.body.appendChild(tooltip);
    
    const rect = event.target.getBoundingClientRect();
    tooltip.style.top = `${rect.top - tooltip.offsetHeight - 5}px`;
    tooltip.style.left = `${rect.left + (rect.width - tooltip.offsetWidth) / 2}px`;
}

function hideTooltip() {
    const tooltips = document.querySelectorAll('.absolute.z-50');
    tooltips.forEach(tooltip => tooltip.remove());
}

function initializeSearch() {
    const searchInput = document.getElementById('global-search');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function(e) {
            performSearch(e.target.value);
        }, 300));
    }
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function performSearch(query) {
    if (query.length < 2) return;
    
    fetch(`/qa/api/search?q=${encodeURIComponent(query)}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        displaySearchResults(data);
    })
    .catch(error => console.error('Search error:', error));
}