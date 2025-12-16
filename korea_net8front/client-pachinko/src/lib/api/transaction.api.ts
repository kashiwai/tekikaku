import { getCookieHeader } from "@/actions/cookie.actions";
import { API_ROUTES } from "@/config/routes.config";
import { transactionConfig } from '@/config/transactions.config';
import fetcher from "@/lib/fetcher";
import { TransactionResponseType } from "@/types/transaction";
import { searchParamUtils } from "@/utils/searchparam.utils";

export const transactionApi = {
    transactions: async ({ page, limit, startDate, endDate, type }: { page: string, limit: string, startDate: string, endDate: string, type: string }): Promise<TransactionResponseType> => {
        const queryParams = searchParamUtils.buildSearchParams({ page, limit, startDate, endDate, type });
        const url = `${API_ROUTES.TRANSACTIONS.LIST}?${queryParams}`;
        
        const res = await fetcher<TransactionResponseType>(url, {
            method: "GET",
            headers: {
                Cookie: await getCookieHeader()
            }
        }, true);

        if (!res.success) return { list: [], total: 0, types: [] };
        return res.data
    },
}