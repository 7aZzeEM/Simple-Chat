/**
 * Chat page logic: chat with friends with swoole server
 */

// Import shared types and base class
import type { UserPayload, Alert, Message } from './modules/Types.js';
import { Rules } from './modules/Rules.js';

class Chat extends Rules
{
    // DOM elements specific to chat form
    private userName: HTMLDivElement;
    private countOnlines: HTMLSpanElement;
    private logoutButton: HTMLButtonElement;
    private chatBox: HTMLDivElement;
    private inputChat: HTMLInputElement;
    private sendButton: HTMLButtonElement;
    private userPayload: UserPayload | null = null;
    private ws: WebSocket | null = null;
    private logedOut: boolean = false;

    constructor ()
    {
        super(); // Initialize shared logic from Rules

        // Get elements page
        this.userName = document.getElementById('userName') as HTMLDivElement;
        this.countOnlines = document.querySelector('span[class="onlines"]') as HTMLSpanElement;
        this.logoutButton = document.getElementById('logoutBtn') as HTMLButtonElement;
        this.chatBox = document.getElementById('chatBox') as HTMLDivElement;
        this.inputChat = document.getElementById('messageInput') as HTMLInputElement;
        this.sendButton = document.getElementById('sendButton') as HTMLButtonElement;

        // Auto focus message input
        this.inputChat.focus();

        // Listening to logout
        this.logoutButton.addEventListener('click', _ => this.logoutUser());
        // Get User Paylaod
        this.getUserPayload();

        // Get messages from DB
        this.getMessage();

        // WebSocket connection
        this.connectionWS();

        // Send message
        this.sendButton.addEventListener('click', _ => this.sendMessage());
        this.inputChat.addEventListener('keyup', (event) => event.key === 'Enter' ? this.sendMessage() : '');
    }

    /**
     * Behavior after alert is closed: redirect or no
     */
    protected onAlertClosed () : void
    {
        if (this.redirect) {
            // send logout request to server then redirect user to / path
            this.logoutUser();
        }
    }

    // Get user paylaod function
    private getUserPayload () : void
    {
        const cookie = JSON.parse(atob(document.cookie.replace('JWT=','').split('.')[1]!));
        this.userPayload = {
            id: Number(cookie.id),
            username: cookie.username,
            email: cookie.email
        };

        // Set username
        this.userName.textContent = this.userPayload.username;
    }

    // Get messages from DB function
    private async getMessage () : Promise<void>
    {
        try {
            const response = await fetch ('/server/api/get-messages', { method: 'POST' });
            const result = await response.json();

            if (!response.ok || !result.status) {
                this.goAlert({status: false, message: result.error || result.message || 'Failed show messages.'});
                this.redirect = true;
                return;
            }

            // Show messages for user
            this.showMessages(JSON.parse(result.messages));
        } catch (e) {
            this.goAlert({status: false, message: 'Failed show messages: ' + e});
            this.redirect = true;
            return;
        }
    }

    // Show messages
    private async showMessages (messages: Message[]) : Promise<void>
    {
        if (messages.length === 0) return;
        await messages.forEach(message => {
            let div = document.createElement('div');
            div.classList.add('message');
            div.classList.add(this.userPayload?.id === message.sender_id ? 'sent' : 'received');
            div.textContent = decodeURIComponent(message.user_message);
            let divTime = document.createElement('div');
            divTime.classList.add('message-time');
            divTime.textContent = this.formatTime(message.created_at);
            div.append(divTime);
            if (div.classList.contains("received")) {
                let username = document.createElement("div");
                username.classList.add("username");
                username.textContent = message.username;
                div.append(username);
            }
            this.chatBox.append(div);
        });
        this.bottomScroll();
    }

    // when get new message scroll bottom
    private bottomScroll () : void
    {
        this.chatBox.scrollTop = this.chatBox.scrollHeight;
    }

    // Format time function
    private formatTime (history: string) : string
    {
        const date = new Date(history);
        return date.toLocaleTimeString('en-GB', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    }

    private async logoutUser(): Promise<void>
    {
        try {
            const res = await fetch('/server/api/logout', { method: 'POST' });
    
            if (!res.ok) {
                this.goAlert({ status: false, message: 'Failed logout user.' });
                return;
            }
    
            this.logedOut = true;
            this.ws?.close();
            window.location.pathname = '/';
        } catch (e) {
            this.goAlert({ status: false, message: "Failed logout user: " + e });
        }
        return;
    }

    private connectionWS () : void
    {
        // connect to WebSocket service
        this.ws = new WebSocket('ws://127.0.0.1:80');

        // when open connection WebSocket
        this.ws.onopen = () => {
            console.log("Connection WebSocket Successfully.");
        }

        // when close connection WebSocket
        this.ws.onclose = () => {
            if (!this.logedOut) {
                this.goAlert({status: false, message: 'Faild connection to WebSocket server, try again later.'});
                this.redirect = true;
                return;
            }
        };

        // when user get message from WebSocket
        this.ws.onmessage = (event) => {
            let data = JSON.parse(event.data);
            if (data.Dtype === "num") {
                this.friendsOnline(Number(data.count - 1));
            } else {
                this.showMessages([JSON.parse(event.data)]);
            }
        }
    }

    // show count users online
    private friendsOnline (count: number) : void
    {
        this.countOnlines.style.display = 'block';
        this.countOnlines.textContent = 'Friends online: ' + count;
    }

    private sendMessage () : void
    {
        if (this.inputChat.value !== '') {
            let message = {
                id: Number(this.userPayload?.id),
                username: this.userPayload?.username,
                message: encodeURIComponent(this.inputChat.value.trim())
            };
            this.inputChat.value = '';
            this.ws?.send(JSON.stringify(message));
        }
    }
}

// Initialize chat logic when page loads
new Chat();