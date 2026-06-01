/**
 * Request Log Analyzer - Main JavaScript
 */

(function() {
    'use strict';

    // Initialize event listeners
    function init() {
        attachFilterListeners();
        initializeCharts();
    }

    // Attach filter form listeners
    function attachFilterListeners() {
        const filterForms = document.querySelectorAll('[data-rla-filter]');
        filterForms.forEach(form => {
            form.addEventListener('submit', e => {
                e.preventDefault();
                form.submit();
            });
        });
    }

    // Initialize charts if Chart.js is available
    function initializeCharts() {
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js not loaded');
            return;
        }

        // Charts will be initialized individually by blade templates
        // This is a placeholder for common chart initialization logic
    }

    // Module exports
    window.RequestLogAnalyzer = {
        init,
        attachFilterListeners,
        initializeCharts
    };

    // Auto-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
