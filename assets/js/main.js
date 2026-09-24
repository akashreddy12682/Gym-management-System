/**
 * Gym Management System - Main JavaScript Client Helper
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Toggle Handler
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        let backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop d-none';
        document.body.appendChild(backdrop);

        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('d-none');
        });

        backdrop.addEventListener('click', function () {
            sidebar.classList.remove('show');
            backdrop.classList.add('d-none');
        });
    }

    // 2. Dynamic Membership End-Date Calculator
    // Used in membership assignment forms
    const planSelect = document.getElementById('plan_id_select');
    const startDateInput = document.getElementById('start_date_input');
    const endDateInput = document.getElementById('end_date_input');
    const planPriceDisplay = document.getElementById('plan_price_display');

    function updateEndDate() {
        if (!planSelect || !startDateInput || !endDateInput) return;

        const selectedOption = planSelect.options[planSelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) return;

        const durationDays = parseInt(selectedOption.getAttribute('data-duration') || '0', 10);
        const planPrice = selectedOption.getAttribute('data-price') || '';

        if (planPriceDisplay && planPrice) {
            planPriceDisplay.textContent = planPrice;
        }

        const startDateVal = startDateInput.value;
        if (startDateVal && durationDays > 0) {
            const startDate = new Date(startDateVal);
            // Add days
            startDate.setDate(startDate.getDate() + durationDays);
            
            // Format YYYY-MM-DD
            const year = startDate.getFullYear();
            const month = String(startDate.getMonth() + 1).padStart(2, '0');
            const day = String(startDate.getDate()).padStart(2, '0');
            
            endDateInput.value = `${year}-${month}-${day}`;
        }
    }

    if (planSelect && startDateInput && endDateInput) {
        planSelect.addEventListener('change', updateEndDate);
        startDateInput.addEventListener('change', updateEndDate);
    }

    // 3. Delete Confirmation Dialog
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm-delete') || 'Are you sure you want to delete this record? This action cannot be undone.';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // 4. Client-side Live Table Filter
    const searchInput = document.getElementById('tableSearchInput');
    const targetTable = document.querySelector('.table-custom tbody');

    if (searchInput && targetTable) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const rows = targetTable.querySelectorAll('tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // 5. Initialize Bootstrap Tooltips if available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});
