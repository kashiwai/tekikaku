import { CookieOptions, DEFAULT_COOKIE_OPTIONS, type ParsedCookie } from "@/config/cookies.config";

export const cookieUtils = {
    parseSetCookieHeader: (cookieHeader: string | null): ParsedCookie[] => {
        if (!cookieHeader) return [];

        // eslint-disable-next-line no-useless-escape
        const setCookies = cookieHeader.split(/,(?=\s*[a-zA-Z0-9_\-]+\=)/);

        return setCookies.map(cookie => {
            const parts = cookie.split(';').map(part => part.trim());
            const [name, value] = parts[0].split('=', 2); // Split only on first '=' to handle values with '='

            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            const attrs = parts.slice(1).reduce((acc: Record<string, any>, attr) => {
                const [key, val] = attr.split('=').map(a => a.trim());
                const normalizedKey = key.toLowerCase();
                acc[normalizedKey] = val ?? true;
                return acc;
            }, {});

            return {
                name,
                value: decodeURIComponent(value || ''), // Decode URL-encoded cookie values
                options: cookieUtils.parseCookieOptions(attrs),
            };
        });
    },
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    parseCookieOptions(attrs: Record<string, any>): CookieOptions {
        const data = {
            httpOnly: true,
            secure: attrs.secure ? process.env.NODE_ENV !== 'development' : false,
            maxAge: attrs['max-age'] ? parseInt(attrs['max-age']) : DEFAULT_COOKIE_OPTIONS.maxAge,
            path: attrs.path || DEFAULT_COOKIE_OPTIONS.path,
            ...(attrs.domain ? { domain: attrs.domain } : (DEFAULT_COOKIE_OPTIONS.domain ? { domain: DEFAULT_COOKIE_OPTIONS.domain } : {}))
        };
        return data
    }

}
