/**
 * Abstract base class for pages that handle user registration flow.
 * Provides shared logic for:
 * - Alert display & close behavior
 * - Form/input visual feedback (opacity & disabled state)
 * - Shared user data (`RegisterData`) handling
 * - Redirect control after success
 */
export class Rules {
    // Shared user data (may be null until loaded or filled)
    userData = null;
    // Shared DOM elements for alert messages (present in both register & verify pages)
    theAlert;
    messageAlert;
    closeAlert;
    // Shared state flags
    redirect = false; // Should redirect after alert closed?
    isProcessing = false; // Prevent duplicate submissions
    constructor() {
        // Query shared alert elements (assumed present in HTML of both pages)
        this.theAlert = document.querySelector('.alert');
        this.messageAlert = document.getElementById('messageAlert');
        this.closeAlert = document.getElementById('closeAlert');
        // Setup shared behavior: close alert
        this.closeAlert.addEventListener('click', () => {
            this.theAlert.style.display = 'none';
            this.onAlertClosed(); // Defer specific behavior to subclass
        });
    }
    /**
     * Display an alert message with color based on status
     * @param message Alert object containing status and optional text
     */
    goAlert(message) {
        this.theAlert.style.display = 'flex';
        this.messageAlert.textContent = message.message ?? '';
        this.messageAlert.style.color = message.status ? '#35a235' : '#b70000';
    }
    /**
     * Toggle visual state (opacity & disabled) of interactive elements during processing
     * @param elements List of inputs/buttons to toggle
     */
    toggleLight(elements) {
        elements.forEach(el => {
            el.style.opacity = el.style.opacity === '' ? '.5' : '';
            el.disabled = !el.disabled;
        });
    }
}
//# sourceMappingURL=Rules.js.map