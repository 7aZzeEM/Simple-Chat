// Type of Register Form
export type RegisterData = {
    Fname: string;
    Email: string;
    Password: string;
    ConfirmPassword: string;
    code?: number;
};

// LoginData
export type LoginData = {
    email: string;
    password: string;
};

// Type of Alert Message
export type Alert = {
    status: boolean;
    message?: string;
};

// Type of UserPayload
export type UserPayload = {
    id: number;
    username: string;
    email: string;
};

// Type of message
export type Message = {
    sender_id: number;
    user_message: string;
    time: string;
    username: string;
    created_at: string;
};