/**
 * Abstract base class for pages that handle user registration flow.
 * Provides shared logic for:
 * - Alert display & close behavior
 * - Form/input visual feedback (opacity & disabled state)
 * - Shared user data (`RegisterData`) handling
 * - Redirect control after success
 */

import type { RegisterData, Alert } from './Types';

export abstract class Rules {
    // Shared user data (may be null until loaded or filled)
    protected userData: RegisterData | null = null;

    // Shared DOM elements for alert messages (present in both register & verify pages)
    protected theAlert: HTMLDivElement;
    protected messageAlert: HTMLHeadingElement;
    protected closeAlert: HTMLAnchorElement;

    // Shared state flags
    protected redirect: boolean = false;      // Should redirect after alert closed?
    protected isProcessing: boolean = false;  // Prevent duplicate submissions

    constructor() {
        // Query shared alert elements (assumed present in HTML of both pages)
        this.theAlert = document.querySelector('.alert') as HTMLDivElement;
        this.messageAlert = document.getElementById('messageAlert') as HTMLHeadingElement;
        this.closeAlert = document.getElementById('closeAlert') as HTMLAnchorElement;

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
    protected goAlert(message: Alert): void {
        this.theAlert.style.display = 'flex';
        this.messageAlert.textContent = message.message ?? '';
        this.messageAlert.style.color = message.status ? '#35a235' : '#b70000';
    }

    /**
     * Toggle visual state (opacity & disabled) of interactive elements during processing
     * @param elements List of inputs/buttons to toggle
     */
    protected toggleLight(elements: (HTMLInputElement | HTMLButtonElement)[]): void {
        elements.forEach(el => {
            el.style.opacity = el.style.opacity === '' ? '.5' : '';
            el.disabled = !el.disabled;
        });
    }

    /**
     * Hook method: define what happens after the alert is closed.
     * Must be implemented by subclasses (e.g., redirect to next page).
     */
    protected abstract onAlertClosed(): void;
}