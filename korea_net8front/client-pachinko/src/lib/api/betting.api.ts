import { BetHistoryResponseType, BetInfo } from "@/types/bethistory";

export const bettingApi = {
    betHistories: async ({ page, limit, startDate, endDate, type }: { page: string, limit: string, startDate: string, endDate: string, type: string | undefined }): Promise<BetHistoryResponseType> => {
        // 外部API無効化 - モックデータを返す
        return { list: [], total: 0, types: [] };
    },
    betInfo: async ({ id, ssr }: { id: string, ssr: boolean }) => {
        // 外部API無効化 - モックデータを返す
        return null;
    }
}