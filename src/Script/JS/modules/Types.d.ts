export type RegisterData = {
    Fname: string;
    Email: string;
    Password: string;
    ConfirmPassword: string;
    code?: number;
};
export type LoginData = {
    email: string;
    password: string;
};
export type Alert = {
    status: boolean;
    message?: string;
};
export type UserPayload = {
    id: number;
    username: string;
    email: string;
};
export type Message = {
    sender_id: number;
    user_message: string;
    time: string;
    username: string;
    created_at: string;
};
//# sourceMappingURL=Types.d.ts.map