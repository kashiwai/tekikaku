export interface CookieOptions {
    httpOnly: boolean;
    secure: boolean;
    maxAge: number;
    path: string;
    domain?: string;
}

export interface ParsedCookie {
    name: string;
    value: string;
    options: CookieOptions;
}

export const DEFAULT_COOKIE_OPTIONS: CookieOptions = {
    httpOnly: true,
    secure: process.env.NODE_ENV !== 'development',
    maxAge: 60 * 60 * 24, // 1 day
    path: '/',
    domain: process.env.NEXT_PUBLIC_COOKIE_DOMAIN
};