/**
 * Abstract base class for pages that handle user registration flow.
 * Provides shared logic for:
 * - Alert display & close behavior
 * - Form/input visual feedback (opacity & disabled state)
 * - Shared user data (`RegisterData`) handling
 * - Redirect control after success
 */
import type { RegisterData, Alert } from './Types';
export declare abstract class Rules {
    protected userData: RegisterData | null;
    protected theAlert: HTMLDivElement;
    protected messageAlert: HTMLHeadingElement;
    protected closeAlert: HTMLAnchorElement;
    protected redirect: boolean;
    protected isProcessing: boolean;
    constructor();
    /**
     * Display an alert message with color based on status
     * @param message Alert object containing status and optional text
     */
    protected goAlert(message: Alert): void;
    /**
     * Toggle visual state (opacity & disabled) of interactive elements during processing
     * @param elements List of inputs/buttons to toggle
     */
    protected toggleLight(elements: (HTMLInputElement | HTMLButtonElement)[]): void;
    /**
     * Hook method: define what happens after the alert is closed.
     * Must be implemented by subclasses (e.g., redirect to next page).
     */
    protected abstract onAlertClosed(): void;
}
//# sourceMappingURL=Rules.d.ts.map