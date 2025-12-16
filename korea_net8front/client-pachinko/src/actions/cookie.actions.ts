"use server"
import { cookies } from "next/headers"
import { cookieUtils } from "@/utils/cookie.utils";
import { redirect } from "next/navigation";
import { ROUTES } from "@/config/routes.config";

export async function getCookieHeader() {
    const cookieStore = await cookies();
    return `user.sid=${cookieStore.get("user.sid")?.value}`;
}

export async function deleteConnectSid() {
    console.log("Deleting connect sid");
    
    const cookieStore = await cookies();
    const isProd = process.env.NODE_ENV === "production";

    cookieStore.delete({
        name: "user.sid",
        domain: isProd ? ".goodfriendsgaming.com" : undefined,
        path: "/",
    });

    redirect(ROUTES.HOME);
}

export async function setCookiesFromResponse(cookieHeader: string | null): Promise<void> {
    const parsedCookies = cookieUtils.parseSetCookieHeader(cookieHeader);
    const cookieStore = await cookies();

    for (const cookie of parsedCookies) {
        // Always decode before saving
        cookieStore.set(cookie.name, cookie.value, {
            httpOnly: true,
            secure: process.env.NODE_ENV !== 'development',
            maxAge: cookie.options.maxAge,
            path: cookie.options.path,
            domain: cookie.options.domain,
        });
    }
}