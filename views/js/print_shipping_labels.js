/**
 * Print Shipping Labels - Bulk Action Handler
 */

document.addEventListener('DOMContentLoaded', function () {
    const printButton = document.querySelector('.js-print-shipping-labels-bulk-action');
    if (!printButton) {
        return;
    }

    const modal = document.getElementById('print_shipping_labels_modal');
    if (!modal) {
        console.error('Print shipping labels modal not found');
        return;
    }

    const form = document.getElementById('print_shipping_labels_form');
    const orderIdsContainer = document.getElementById('print_labels_order_ids_container');
    const submitButton = modal.querySelector('.js-submit-print-labels');

    // Handle print button click
    printButton.addEventListener('click', function (event) {
        event.preventDefault();

        // Get selected order IDs from checkboxes
        const checkboxes = document.querySelectorAll('.js-bulk-action-checkbox:checked');
        const orderIds = Array.from(checkboxes).map(cb => cb.value);

        if (orderIds.length === 0) {
            if (window.showErrorMessage) {
                window.showErrorMessage('You must select at least one order.');
            } else {
                alert('You must select at least one order.');
            }
            return;
        }

        // Clear previous order IDs
        orderIdsContainer.innerHTML = '';

        // Add hidden inputs for each order ID
        orderIds.forEach(function (orderId) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'order_ids[]';
            input.value = orderId;
            orderIdsContainer.appendChild(input);
        });

        // Update modal title with count
        const modalTitle = modal.querySelector('.modal-title');
        if (modalTitle) {
            modalTitle.textContent = 'Print shipping labels (' + orderIds.length + ' order' + (orderIds.length > 1 ? 's' : '') + ')';
        }

        // Show modal using Bootstrap
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        } else if (typeof $ !== 'undefined' && $.fn.modal) {
            $(modal).modal('show');
        }
    });

    // Handle form submission
    if (submitButton) {
        submitButton.addEventListener('click', function (event) {
            event.preventDefault();
            form.submit();
        });
    }
});
