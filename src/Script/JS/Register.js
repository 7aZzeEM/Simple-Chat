/**
 * Registration page logic: collects user data, validates, and sends to API
 */
import { Rules } from './modules/Rules.js';
class RegisterAccount extends Rules {
    // DOM elements specific to registration form
    formRegister;
    usernameE;
    emailE;
    passwordE;
    passwordConfirmE;
    registerButton;
    constructor() {
        super(); // Initialize shared logic from Rules
        // Get form and input elements
        this.formRegister = document.getElementById('registerForm');
        this.usernameE = document.getElementById('fullname');
        this.emailE = document.getElementById('email');
        this.passwordE = document.getElementById('password');
        this.passwordConfirmE = document.getElementById('confirmPassword');
        this.registerButton = document.querySelector('button[type="submit"]');
        // Bind form submission
        this.formRegister.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleRegistration();
        });
    }
    /**
     * Behavior after alert is closed: redirect to verification if registration succeeded
     */
    onAlertClosed() {
        if (this.redirect) {
            window.location.pathname = '/Verification_code';
        }
    }
    /**
     * Main registration workflow
     */
    async handleRegistration() {
        const allInputs = [this.usernameE, this.emailE, this.passwordE, this.passwordConfirmE];
        const allControls = [...allInputs, this.registerButton];
        this.toggleLight(allControls);
        this.collectFormData();
        const validation = this.validateData(this.userData);
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
            }
            else {
                // Save to localStorage for verification step
                localStorage.setItem('pendingEmail', JSON.stringify(this.userData));
                this.goAlert({ status: true, message: result.message });
                this.redirect = true;
            }
        }
        catch {
            this.goAlert({ status: false, message: "Failed to send registration request." });
        }
        finally {
            this.toggleLight(allControls);
        }
    }
    /**
     * Fill userData from form inputs
     */
    collectFormData() {
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
    validateData(data) {
        const validName = /^(?!.*(<|"|'|=|>|javascript:|vbscript:|on\w+\s*=|<\?|<%|<!\[CDATA)).*$/.test(data.Fname.toLowerCase());
        const validEmail = /^[a-zA-Z0-9_.-]+@(gmail|yahoo|hotmail)+\.[a-z]{2,}$/.test(data.Email);
        const validPass = /^[a-zA-Z0-9_.@!~$%&*()\s-]{10,}$/.test(data.Password);
        const validConfirmPass = data.Password === data.ConfirmPassword;
        if (!validName)
            return { status: false, message: "This name is not allowed." };
        if (!validEmail)
            return { status: false, message: "Only (gmail, yahoo, hotmail) emails are allowed." };
        if (!validPass)
            return { status: false, message: "Password must be at least 10 characters." };
        if (!validConfirmPass)
            return { status: false, message: "Passwords do not match." };
        return { status: true };
    }
}
// Initialize registration logic when page loads
new RegisterAccount();
//# sourceMappingURL=Register.js.map