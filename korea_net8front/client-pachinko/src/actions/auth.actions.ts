"use server";
import { cookies, headers } from "next/headers";

import { API_ROUTES } from "@/config/routes.config";
import fetcher from "@/lib/fetcher";
import { AuthSchemas } from "@/validations/auth.schemas";
import { User } from "@/types/user.types";
import { getCookieHeader } from "./cookie.actions";

export async function login(values: AuthSchemas['koreaLogin'], timezone: string) {
    const headersList = await headers();
    const userAgent = headersList.get("user-agent") || "";
    
    const body = {
        loginId: values.loginId,
        pw: values.password,
        role: "user",
        timezone
    }

    const loginResponse = await fetcher(API_ROUTES.AUTH.LOGIN, {
        method: "POST",
        body: JSON.stringify(body),
        headers: {
            "User-Agent": userAgent
        }
    })

    if (!loginResponse.success) return loginResponse;

    const successResponse = await fetcher<User>(API_ROUTES.AUTH.LOGIN_SUCCESS, {
        method: "POST",
        headers: {
            Cookie: await getCookieHeader(),
            "User-Agent": userAgent
        },
        body: JSON.stringify(body),
    });
    
    return successResponse;
}

export async function logout() {
    const cookieStore = await cookies();
    const isProd = process.env.NODE_ENV === "production";

    const res = await fetcher(API_ROUTES.AUTH.LOGOUT, {
        method: "POST",
        headers: {
            Cookie: await getCookieHeader()
        }
    });

    cookieStore.delete({
        name: "user.sid",
        domain: isProd ? ".goodfriendsgaming.com" : undefined,
        path: "/",
    });

    return res;
}

export async function resetPassword(values: AuthSchemas['resetPassword']) {
    const body = {
        newPw: values.new_password,
        email: values.email,
        code: values.email_code
    }

    const res = await fetcher(API_ROUTES.AUTH.RESET_PASSWORD, {
        method: "PATCH",
        body: JSON.stringify(body)
    })

    return res;
}

export async function sendAuthCode(email: string, reset: boolean = false) {
    const body = {
        email,
        reset
    }

    const res = await fetcher(API_ROUTES.AUTH.SEND_EMAIL_CODE, {
        method: "POST",
        body: JSON.stringify(body),
    })

    return res;
}