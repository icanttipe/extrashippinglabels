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

    let bsModal = null;

    // Handle print button click
    printButton.addEventListener('click', function (event) {
        event.preventDefault();

        // Get selected order IDs from checkboxes
        const checkboxes = document.querySelectorAll('.js-bulk-action-checkbox:checked');
        const orderIds = Array.from(checkboxes).map(cb => cb.value);

        if (orderIds.length === 0) {
            showGrowl('error', 'You must select at least one order.');
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

        // Reset form to default
        const defaultRadio = form.querySelector('input[value="missing_only"]');
        if (defaultRadio) {
            defaultRadio.checked = true;
        }

        // Show modal
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        } else if (typeof $ !== 'undefined' && $.fn.modal) {
            $(modal).modal('show');
        }
    });

    // Handle form submission via AJAX
    if (submitButton) {
        submitButton.addEventListener('click', function (event) {
            event.preventDefault();

            const formData = new FormData(form);

            // Disable button and show loading state
            submitButton.disabled = true;
            const originalText = submitButton.textContent;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading...';

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Close modal first
                closeModal();

                // Show errors if any
                const errors = data.errors || [];
                if (errors.length > 0) {
                    showGrowl('error', errors.join('<br>'), 999999);
                }

                // Download PDF if available
                if (data.pdf && data.pdf.length > 0) {
                    downloadPdf(data.pdf, data.filename || 'shipping_labels.pdf');
                    showGrowl('notice', (data.labelsCount || 1) + ' label(s) downloaded successfully.');
                } else if (!data.pdf && errors.length === 0) {
                    showGrowl('warning', 'No labels found for the selected orders.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showGrowl('error', 'An unexpected error occurred. Please try again.');
                closeModal();
            })
            .finally(() => {
                // Restore button state
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            });
        });
    }

    /**
     * Close the modal
     */
    function closeModal() {
        if (bsModal) {
            bsModal.hide();
        } else if (typeof $ !== 'undefined' && $.fn.modal) {
            $(modal).modal('hide');
        }
    }

    /**
     * Download PDF from base64 data
     */
    function downloadPdf(base64Data, filename) {
        try {
            const byteCharacters = atob(base64Data);
            const byteNumbers = new Array(byteCharacters.length);
            for (let i = 0; i < byteCharacters.length; i++) {
                byteNumbers[i] = byteCharacters.charCodeAt(i);
            }
            const byteArray = new Uint8Array(byteNumbers);
            const blob = new Blob([byteArray], { type: 'application/pdf' });

            // Create download link
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();

            // Cleanup
            setTimeout(() => {
                document.body.removeChild(link);
                URL.revokeObjectURL(link.href);
            }, 100);
        } catch (e) {
            console.error('PDF download error:', e);
            showGrowl('error', 'Failed to download PDF: ' + e.message);
        }
    }

    /**
     * Show growl notification (PrestaShop style)
     * Available types: notice, warning, error
     */
    function showGrowl(type, message, duration = null) {
        if (typeof $ !== 'undefined' && $.growl && typeof $.growl[type] === 'function') {
            $.growl[type]({ message: message, duration: Number.isInteger(duration) ? duration : undefined});
        } else {
            console.log('[' + type.toUpperCase() + ']', message);
            if (type === 'error') {
                alert(message);
            }
        }
    }
});
