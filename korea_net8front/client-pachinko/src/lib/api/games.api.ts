import { GameListResponse } from "@/config/game.config";
import { GameItem } from "@/types/game.types";

export const gamesApi = {
    favorites: async () => {
        // 外部API無効化 - モックデータを返す
        return { list: [], total: 0 };
    },

    games: async ({ type, game, limit, vendor, page, search = "", ssr = false }: {
        type: string,
        game: string,
        limit: string,
        vendor: string,
        page: string,
        search?: string;
        ssr: boolean
    }): Promise<GameListResponse> => {
        // 外部API無効化 - モックデータを返す
        return { 
            types: [], 
            list: [], 
            total: 0, 
            vendors: { rank: null, vendor: '', image: '' } 
        };
    },
}