import { FaqListResponse, NoticeListResponse } from "@/config/notice.config";

export const noticeApi = {
    noticeList: async ({ page, limit, isUse, }: { page: string, limit: string, isUse: string }): Promise<NoticeListResponse> => {
        // 外部API無効化 - モックデータを返す
        return { list: [], total: 0 };
    },

    faqList: async ({ page, limit, type, }: { page: string, limit: string, type: string }): Promise<FaqListResponse> => {
        // 外部API無効化 - モックデータを返す
        return { list: [], total: 0 };
    },
}
