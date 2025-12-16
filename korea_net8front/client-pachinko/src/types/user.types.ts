export type walletType = {
    bonus: {
        locked: number;
        unlocked: number;
    };
    cashback: {
        live: number;
        week: number;
        month: number;
    };
    money: number;
}


export type User = {
    id: number;
    loginId: string;
    siteId: number;
    agencyId: number;
    referral: number;
    userCode: string;
    info: {
        ip: string;
        os: Record<string, unknown>; // unknown shape
        exp: number;
        needExp: number;
        spin: number;
        level: number;
        phone: string;
        client: {
            name: string;
            type: string;
            version: string;
        };
        device: {
            id: string;
            type: string;
            brand: string;
            model: string;
        };
        nickname: string;
        isApprove: string;
        sessionId: string;
        transaction: {
            bank: string;
            bankNumber: string;
            realname: string;
            withdrawalType: string;
        };
        nextLevelData: {
            bonus: number;
            needExp: number;
            name: string;
        };
        curLevelData: {
            bonus: number;
            needExp: number;
            name: string;
        };
    };
    rollingCommission: {
        games: {
            slot: number;
            casino: number;
        };
        isUse: boolean;
    };
    bonus: {
        locked: number;
        unlocked: number;
    };
    attendance: {
        dates: string[];
        totalReward: number;
        streakReward: number;
        count: number;
        total: number;
        streakDays: number;
    };
    isUse: boolean;
    roulette: {
        count: number;
        total: number;
    };
    wallets: {
        money: number;
        vault: number;
    };
    rolling: {
        games: {
            slot: number;
            casino: number;
            holdem: number;
            sports: number;
            virtual: number;
            pachinko: number;
        };
        isUse: boolean;
    };
    losingCommission: {
        games: {
            slot: number;
            casino: number;
        };
        isUse: boolean;
    };
    eventBans: { eventId: number, type: BanType, siteUse: boolean }[],
    gameBans: { eventId: number, type: BanType, siteUse: boolean }[]
};

export type BanType = "casino" | "slot" | "holdem" | "virtual" | "sports" | "minigame" | "pachinko";

export type UserInfo = {
    avatar: string;
    nickname: string;
    telecom: string;
    phone: string;
    email: string;
    transaction: UserTransaction;
    level: number;
    exp: number;
    spin: number;
    ip: string | null;
    status: string;
    isApprove: "application" | string;
    isInterested: boolean;
    isDormant: boolean;
}

export type UserTransaction = {
    realname: string;
    withdrawalType: string;
    bank: string;
    bankNumber: string;
    pw: string;
}

export type Favorite = { id: string };

export type Wallet = {
    bonus: {
        locked: number;
        unlocked: number;
    },
    cashback: {
        live: number;
        week: number;
        month: number;
    },
    money: number;
}

export type UserSettings = {
    useFirstDeposit: boolean
    useNewDayDeposit: boolean
    useEverytimeDeposit: boolean
    useLiveCashback: boolean
    useWeeklyCashback: boolean
    useMonthlyCashback: boolean
    liveChatBan: boolean
    noteBan: boolean
}

export type UserAttendance = {
    list: string[];
    ids: string[];
}
export type Rolling = {
    inUse: boolean;
    sports: number;
    casino: number;
    slot: number;
    holdem: number;
    minigame: number;
    virtual: number;
}