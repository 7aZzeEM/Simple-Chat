/**
 * Login page logic: collects user data, validates, and sends to API
 */

// Import shared types and base class
import type { LoginData, Alert } from './modules/Types.js';
import { Rules } from './modules/Rules.js';
class LoginUser extends Rules
{
    // DOM elements specific to login
    private loginForm: HTMLFormElement;
    private email: HTMLInputElement;
    private password: HTMLInputElement;
    private loginButton: HTMLButtonElement;
    private loginData: LoginData | null = null;

    constructor () {
        super(); // Initialize shared logic from Rules

        // Get form and input elements
        this.loginForm = document.getElementById('loginForm') as HTMLFormElement;
        this.email = document.getElementById('email') as HTMLInputElement;
        this.password = document.getElementById('password') as HTMLInputElement;
        this.loginButton = document.querySelector('button[type="submit"]') as HTMLButtonElement;

        // Bind form submission
        this.loginForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleLogin();
        });
    }

    /**
     * Main login workflow
     */
    private async handleLogin() : Promise<void> {
        this.isProcessing = true;
        this.toggleLight([this.email, this.password, this.loginButton]);

        this.collectFormData();
        const validData = this.validateData(this.loginData!);

        if (!validData.status) {
            this.goAlert(validData);
            return;
        }

        try {
            const response = await fetch ('/server/api/login', {
                method: 'POST',
                body: JSON.stringify(this.loginData)
            });

            const result = await response.json();

            if (!response.ok) {
                this.goAlert({status: false, message: result.error || "Falid API Login"});
                return;
            }

            if (!result.status) {
                this.goAlert({status: false, message: result.message});
                return;
            }

            this.goAlert({ status: true, message: result.message });
            this.redirect = true;
        } catch {
            this.goAlert({ status: false, message: "Failed to send login request." });
        }
    }

    /**
     * Fill userData from form inputs
     */
    private collectFormData () : void {
        this.loginData = {
            email: this.email.value.trim(),
            password: this.password.value.trim()
        };
    }

    /**
     * Validate user input according to security & format rules
     */
    private validateData (data: LoginData) : Alert {
        const validEmail = /^[a-zA-Z0-9_.-]+@(gmail|yahoo|hotmail)+\.[a-z]{2,}$/.test(data.email);
        const validPass = /^[a-zA-Z0-9_.@!~$%&*()\s-]{10,}$/.test(data.password);
        if (!validEmail) return { status: false, message: "Only (gmail, yahoo, hotmail) emails are allowed." };
        if (!validPass) return { status: false, message: "Password must be at least 10 characters." };
        return { status: true };
    }

    /**
     * Behavior after alert is closed: redirect to chat if login succeeded
     */
    protected onAlertClosed() : void {
        this.isProcessing = false;
        this.toggleLight([this.email, this.password, this.loginButton]);
        if (this.redirect) {
            window.location.pathname = '/Chat';
        }
    }
}

// Initialize login logic when page loads
new LoginUser();