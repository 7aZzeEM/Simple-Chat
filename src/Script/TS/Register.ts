/**
 * Registration page logic: collects user data, validates, and sends to API
 */

// Import shared types and base class
import type { RegisterData, Alert } from './modules/Types.js';
import { Rules } from './modules/Rules.js';

class RegisterAccount extends Rules {
    // DOM elements specific to registration form
    private formRegister: HTMLFormElement;
    private usernameE: HTMLInputElement;
    private emailE: HTMLInputElement;
    private passwordE: HTMLInputElement;
    private passwordConfirmE: HTMLInputElement;
    private registerButton: HTMLButtonElement;

    constructor() {
        super(); // Initialize shared logic from Rules

        // Get form and input elements
        this.formRegister = document.getElementById('registerForm') as HTMLFormElement;
        this.usernameE = document.getElementById('fullname') as HTMLInputElement;
        this.emailE = document.getElementById('email') as HTMLInputElement;
        this.passwordE = document.getElementById('password') as HTMLInputElement;
        this.passwordConfirmE = document.getElementById('confirmPassword') as HTMLInputElement;
        this.registerButton = document.querySelector('button[type="submit"]') as HTMLButtonElement;

        // Bind form submission
        this.formRegister.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleRegistration();
        });
    }

    /**
     * Behavior after alert is closed: redirect to verification if registration succeeded
     */
    protected onAlertClosed(): void {
        if (this.redirect) {
            window.location.pathname = '/Verification_code';
        }
    }

    /**
     * Main registration workflow
     */
    private async handleRegistration(): Promise<void> {
        const allInputs = [this.usernameE, this.emailE, this.passwordE, this.passwordConfirmE];
        const allControls = [...allInputs, this.registerButton];

        this.toggleLight(allControls);

        this.collectFormData();
        const validation = this.validateData(this.userData!);
        if (!validation.status) {
            this.goAlert(validation);
            this.toggleLight(allControls); // Re-enable immediately on error
            return;
        }

        try {
            const response = await fetch('/server/api/register', {
                method: 'POST',
                body: JSON.stringify(this.userData),
            });

            const result = await response.json();

            if (!response.ok || !result.status) {
                this.goAlert({ status: false, message: result.message || result.error || "Registration failed." });
            } else {
                // Save to localStorage for verification step
                localStorage.setItem('pendingEmail', JSON.stringify(this.userData));
                this.goAlert({ status: true, message: result.message });
                this.redirect = true;
            }
        } catch {
            this.goAlert({ status: false, message: "Failed to send registration request." });
        } finally {
            this.toggleLight(allControls);
        }
    }

    /**
     * Fill userData from form inputs
     */
    private collectFormData(): void {
        this.userData = {
            Fname: this.usernameE.value,
            Email: this.emailE.value,
            Password: this.passwordE.value,
            ConfirmPassword: this.passwordConfirmE.value,
        };
    }

    /**
     * Validate user input according to security & format rules
     */
    private validateData(data: RegisterData): Alert {
        const validName = /^(?!.*(<|"|'|=|>|javascript:|vbscript:|on\w+\s*=|<\?|<%|<!\[CDATA)).*$/.test(data.Fname.toLowerCase());
        const validEmail = /^[a-zA-Z0-9_.-]+@(gmail|yahoo|hotmail)+\.[a-z]{2,}$/.test(data.Email);
        const validPass = /^[a-zA-Z0-9_.@!~$%&*()\s-]{10,}$/.test(data.Password);
        const validConfirmPass = data.Password === data.ConfirmPassword;

        if (!validName) return { status: false, message: "This name is not allowed." };
        if (!validEmail) return { status: false, message: "Only (gmail, yahoo, hotmail) emails are allowed." };
        if (!validPass) return { status: false, message: "Password must be at least 10 characters." };
        if (!validConfirmPass) return { status: false, message: "Passwords do not match." };

        return { status: true };
    }
}

// Initialize registration logic when page loads
new RegisterAccount();