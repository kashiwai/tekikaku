export type GameLaunchResponse = {
    link: string | null;
    title: string;
    api: string;
    type: string;
};

export type GameResponseType = {
    list: GameItem[];
    total: number;
    vendors: string[];
    types: string[];
    pagination: PaginationType;
    providers: []
}

export type GameItem = {
    gameId: string;
    api: string;
    langs: {
        ko: string;
        en: string;
        ja: string;
        zh: string;
    };
    rank: number;
    thumbnail: string;
    thumbnails: object;
    title: string;
    type: string;
    vendor: string;
    exclude: boolean;
    isFavorite: boolean;
    game_code: string;
    game_name: string;
    banner: string;
    status: number;
    provider_code: string;
    isSiteUse: boolean;
    isUserUse: boolean;
}

export type PaginationType = {
    currentPage: number;
    totalPages: number;
    totalItems: number;
    itemsPerPage: number;
    hasNextPage: boolean;
    hasPrevPage: boolean;
    nextPage: number | null;
    prevPage: number | null;
};