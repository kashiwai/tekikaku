"use server";
import { headers } from "next/headers";

import { getCookieHeader } from "@/actions/cookie.actions";
import { API_ROUTES } from "@/config/routes.config";
import fetcher from "@/lib/fetcher";
import { BonusResponse } from "@/types/bonus.types";
import { BonusSchemas } from "@/validations/bonus.schema";
import { redirect, RedirectType } from "next/navigation";
import { User } from "@/types/user.types";

export async function redirectUser(url: string, type?: RedirectType) {
    return redirect(url, type)
}

export async function getRouletteResult(ssr: boolean = false): Promise<{ id: number, siteId: number, title: string, odds: number, bonus: number, isLock: boolean, rand: number, message: string } | null> {
    const res = await fetcher<{ id: number, siteId: number, title: string, odds: number, bonus: number, isLock: boolean, rand: number, message: string }>(API_ROUTES.WHEEL.ROULLETE, {
        method: "GET",
        headers: {
            Cookie: await getCookieHeader()
        },
    }, ssr);
    if (!res.success) return null;
    return res.data;
}

export async function claimAttendanceBonus(date: string, ssr: boolean = false) {
    const cookie = (await headers()).get('cookie') || '';

    const response = await fetcher<Partial<User>>(API_ROUTES.ATTENDANCE, {
        method: "POST",
        body: JSON.stringify({
            date,
        }),
        headers: {
            cookie
        },
    }, ssr);

    return response
}

export async function claimUnlockBonus(values: BonusSchemas['claimBonus'], ssr: boolean = false) {
    const cookie = (await headers()).get('cookie') || '';

    const response = await fetcher(API_ROUTES.BONUS.CLAIM_BONUS, {
        method: "POST",
        body: JSON.stringify({
            unlocked: Number(values.amount)
        }),
        headers: {
            cookie
        },
    }, ssr);

    return response
}

export async function getBonusDetails(ssr: boolean = false): Promise<BonusResponse | null> {
    const cookie = (await headers()).get('cookie') || '';

    const res = await fetcher<BonusResponse>(API_ROUTES.BONUS.DETAIL, {
        method: "GET",
        headers: {
            cookie
        },
    }, ssr);

    if (!res.success) return null;
    return res.data;
}

export async function getBonusPublicDetails() {
    const cookie = (await headers()).get('cookie') || '';

    const response = await fetcher(API_ROUTES.BONUS.PUBLIC_DETAIL, {
        method: "GET",
        headers: {
            cookie
        },
    });

    return response
}

export async function getReferralBonus(userCode: string, ssr: boolean = false) {
    const cookie = (await headers()).get('cookie') || '';

    const response = await fetcher(API_ROUTES.REFERRAL, {
        method: "POST",
        body: JSON.stringify({
            code: userCode
        }),
        headers: {
            cookie
        },
    }, ssr);

    return response
}

export async function getCouponBonus(code: string, ssr: boolean = false) {
    const cookie = (await headers()).get('cookie') || '';

    const response = await fetcher(API_ROUTES.COUPON, {
        method: "POST",
        body: JSON.stringify({
            code
        }),
        headers: {
            cookie
        },
    }, ssr);

    return response
}