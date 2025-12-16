import { GameItem } from "@/types/game.types";

export type GameListResponse = {
    types: string[];
    list: GameItem[];
    total: number;
    vendors: {
        rank: string | null;
        vendor: string;
        image: string;
    };
};

export const gameConfig = {
    pagination: {
        limit: 99
    },
    gameType: ["casino", "slot", "pachinko"], // ← important
} as const;

export type GameType = typeof gameConfig.gameType[number];