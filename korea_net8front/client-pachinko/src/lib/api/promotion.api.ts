import { PromotionType } from "@/types/promotion.types";

export const promotionApi = {
    promotions: async ({ page, limit, isUse, isShow }: { page: string, limit: string, isUse?: boolean | boolean, isShow?: boolean }): Promise<{ list: PromotionType[]; total: number }> => {
        // 外部API無効化 - モックデータを返す
        return { list: [], total: 0 };
    },

    promotionById: async (id: string): Promise<PromotionType | null> => {
        // 外部API無効化 - モックデータを返す
        return null;
    }
}