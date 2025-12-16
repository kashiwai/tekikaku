import { BannerData } from "@/types/banner.types";

export const bannersApi = {
    banners: async (): Promise<BannerData> => {
        // 外部API無効化 - モックデータを返す
        return { 
            game: [], 
            logout: [], 
            category: [] 
        };
    }
}