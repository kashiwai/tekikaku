import { NextRequest, NextResponse } from "next/server";
import createMiddleware from "next-intl/middleware";

import { routing } from "./i18n/routing";
import { API_ROUTES, ROUTES } from "./config/routes.config";

function flattenRoutes<T extends Record<string, any>>(obj: T): string[] {
    return Object.values(obj).flatMap((value) => {
        if (typeof value === "string") return [value];
        if (typeof value === "object" && value !== null) return flattenRoutes(value);
        return [];
    });
}

const protectedPages = flattenRoutes({
    FAVORITES: ROUTES.FAVORITES,
    SETTINGS: ROUTES.SETTINGS,
    ACCOUNT: ROUTES.ACCOUNT,
    PROFILE: ROUTES.PROFILE,
});

const intlMiddleware = createMiddleware(routing);

const GF_API_KEY = `Bearer ${process.env.NEXT_PUBLIC_NEW_GAMING_AUTHORIZATION}`;
const url = `${process.env.NEXT_PUBLIC_API_URL}/${API_ROUTES.AUTH.LOGIN_CHECK}`;

export default async function middleware(req: NextRequest) {
    const intlResponse = intlMiddleware(req);
    const pathname = req.nextUrl.pathname;

    const locale = pathname.split("/")[1];
    const localePrefix = routing.locales.includes(locale as any) ? `/${locale}` : "";

    const isProtectedPage = protectedPages.some((page) => pathname.includes(page as any));

    const sessionId = req.cookies.get("user.sid")?.value;

    try {
        const res = await fetch(url, {
            method: "POST",
            headers: {
                Authorization: GF_API_KEY,
                "Content-Type": "application/json",
                Cookie: `user.sid=${sessionId}`,
            },
            credentials: "include",
        });

        const data = await res.json();
        const isMaintenance = data?.message === "MAINTENANCE";
        const isMaintenancePage = pathname.includes(ROUTES.MAINTENANCE);

        // If backend says maintenance ended but user still on maintenance page
        if (isMaintenancePage && !isMaintenance) {
            const res = NextResponse.redirect(new URL(`${localePrefix}/`, req.url));
            res.cookies.delete("maintenance.title");
            res.cookies.delete("maintenance.description");
            return res;
        }

        // Maintenance active
        if (isMaintenance) {
            const title = data.title || "Maintenance Mode";
            const description = data.description || "";

            if (isMaintenancePage) {
                // Already on maintenance page → set cookies only
                const res = intlResponse;
                res.cookies.set("maintenance.title", title, { path: "/" });
                res.cookies.set("maintenance.description", description, { path: "/" });
                return res;
            }

            // Not on maintenance page → redirect to localized maintenance page
            const maintenanceUrl = new URL(`${localePrefix}${ROUTES.MAINTENANCE}`, req.url);

            const res = NextResponse.redirect(maintenanceUrl);
            res.cookies.set("maintenance.title", title, { path: "/" });
            res.cookies.set("maintenance.description", description, { path: "/" });
            return res;
        }

        // Protected routes
        if (isProtectedPage && (!sessionId || data.code !== 1000)) {
            return NextResponse.redirect(new URL(`${localePrefix}/`, req.url));
        }

        // Normal case — return the intl response
        return intlResponse;
    } catch (error) {
        // Backend unavailable - allow access without auth check (development mode)
        if (isProtectedPage && !sessionId) {
            return NextResponse.redirect(new URL(`${localePrefix}/`, req.url));
        }
        return intlResponse;
    }
}

export const config = {
    matcher: [
        '/((?!api|trpc|_next/static|_next/image|_vercel|public|favicon.ico|robots.txt|sitemap.xml|\\.well-known|.*\\..*$).*)',
    ],
};