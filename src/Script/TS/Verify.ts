/**
 * Verification code page: validates 6-digit code and completes registration
 */

// Import shared types and base class
import type { RegisterData, Alert } from './modules/Types.js';
import { Rules } from './modules/Rules.js';

class verifyCode extends Rules {
    // DOM elements specific to verification form
    private codeInput: HTMLInputElement;
    private resendLink: HTMLAnchorElement;
    private verifyButton: HTMLButtonElement;
    private formVerify: HTMLFormElement;

    constructor() {
        super();

        this.loadPendingUserData();
        this.codeInput = document.getElementById('codeInput') as HTMLInputElement;
        this.resendLink = document.getElementById('Resend') as HTMLAnchorElement;
        this.verifyButton = document.querySelector('button[type="submit"]') as HTMLButtonElement;
        this.formVerify = document.getElementById('verifyForm') as HTMLFormElement;

        this.setupEventListeners();
    }

    /**
     * Behavior after alert is closed: redirect to chat if verification succeeded
     */
    protected onAlertClosed(): void {
        this.toggleLight([this.codeInput, this.verifyButton]);
        if (this.redirect) {
            localStorage.removeItem('pendingEmail');
            window.location.pathname = '/Chat';
        }
    }

    /**
     * Load user data from localStorage; redirect to register if missing or invalid
     */
    private loadPendingUserData(): void {
        const stored = localStorage.getItem('pendingEmail');
        if (!stored) {
            window.location.pathname = '/Register';
            return;
        }

        try {
            this.userData = JSON.parse(stored);
        } catch {
            localStorage.removeItem('pendingEmail');
            window.location.pathname = '/Register';
        }
    }

    /**
     * Setup all page-specific event listeners
     */
    private setupEventListeners(): void {
        // Allow only digits in code input
        this.codeInput.addEventListener('keydown', (e) => {
            const allowedKeys = [8, 9, 13, 27, 37, 38, 39, 40, 46]; // Backspace, arrows, etc.
            if (allowedKeys.includes(e.keyCode)) return;
            if (/^[^0-9]$/.test(e.key)) e.preventDefault();
        });

        this.resendLink.addEventListener('click', () => this.resendVerificationCode());
        this.formVerify.addEventListener('submit', (e) => {
            e.preventDefault();
            this.verifyUserCode();
        });
    }

    /**
     * Resend verification code via API
     */
    private async resendVerificationCode(): Promise<void> {
        if (this.isProcessing || !this.userData) return;

        this.isProcessing = true;
        this.toggleLight([this.codeInput, this.verifyButton]);

        try {
            const response = await fetch('/server/api/resend-code', {
                method: 'POST',
                body: JSON.stringify(this.userData),
            });

            const result = await response.json();

            if (!response.ok || !result.status) {
                this.goAlert({ status: false, message: result.message || result.error || "Resend failed." });
            } else {
                this.goAlert({ status: true, message: result.message });
            }
        } catch {
            this.goAlert({ status: false, message: "Failed to resend code." });
        } finally {
            this.isProcessing = false;
        }
    }

    /**
     * Verify the 6-digit code entered by the user
     */
    private async verifyUserCode(): Promise<void> {
        if (this.isProcessing || !this.userData) return;

        const codeStr = this.codeInput.value.trim();
        if (codeStr.length !== 6) {
            this.goAlert({ status: false, message: "Verification code must be 6 digits." });
            return;
        }

        const codeNum = Number(codeStr);
        if (!Number.isInteger(codeNum)) {
            this.goAlert({ status: false, message: "Code must be a valid number." });
            return;
        }

        // Attach code to userData for API submission
        this.userData.code = codeNum;

        this.isProcessing = true;
        this.toggleLight([this.codeInput, this.verifyButton]);

        try {
            const response = await fetch('/server/api/verify-code', {
                method: 'POST',
                body: JSON.stringify(this.userData),
            });

            const result = await response.json();

            if (!response.ok || !result.status) {
                this.goAlert({ status: false, message: result.message || result.error || "Verification failed." });
            } else {
                this.redirect = true;
                this.goAlert({ status: true, message: result.message });
            }
        } catch {
            this.goAlert({ status: false, message: "Failed to verify code." });
        } finally {
            this.isProcessing = false;
        }
    }
}

// Initialize verification logic when page loads
new verifyCode();