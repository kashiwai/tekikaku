import { API_ROUTES } from "@/config/routes.config";
import fetcher from "../fetcher";
import { getCookieHeader } from "@/actions/cookie.actions";
import { DepositAddressResponse, WithdrawResponse } from "@/types/wallet.types";

export const walletApi = {
    withdrawal: {
        get: async ({ ssr = false }: { ssr: boolean }): Promise<WithdrawResponse | null> => {
            const url = `${API_ROUTES.WALLET.WITHDRAWAL.INFO}`;
            const res = await fetcher<WithdrawResponse>(url, {
                method: "GET",
                headers: {
                    Cookie: await getCookieHeader()
                }
            }, ssr);

            if (!res.success) return null;
            return res.data
        },
    },
    deposit: {
        address: async ({ ssr = false }: { ssr: boolean }): Promise<DepositAddressResponse | null> => {
            const url = `${API_ROUTES.WALLET.DEPOSIT.ADDRESS}`
            const res = await fetcher<DepositAddressResponse>(url, {
                method: "POST",
                headers: {
                    Cookie: await getCookieHeader()
                }
            })

            if (!res.success) return null;
            return res.data;
        }
    }
}