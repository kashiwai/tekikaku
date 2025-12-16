"use server"
import fetcher from "@/lib/fetcher";
import { getCookieHeader } from "./cookie.actions";
import { BonusResponse } from "@/types/bonus.types";
import { API_ROUTES } from "@/config/routes.config";

export async function deposit({ amount, bankAccountId, ssr }: { amount: string, bankAccountId: number, ssr: boolean }) {
    const res = await fetcher<BonusResponse>(API_ROUTES.WALLET.DEPOSIT.DEPOSIT, {
        method: "POST",
        headers: {
            cookie: await getCookieHeader(),
        },
        body: JSON.stringify({ amount, bankAccountId })
    }, ssr);

    return res;
}

export async function withdraw({ amount, pw, ssr }: { amount: string, pw: string, ssr: boolean }) {
    const res = await fetcher<BonusResponse>(API_ROUTES.WALLET.WITHDRAWAL.WITHDRAWAL, {
        method: "POST",
        headers: {
            cookie: await getCookieHeader(),
        },
        body: JSON.stringify({ amount, pw })
    }, ssr);

    return res;
}
