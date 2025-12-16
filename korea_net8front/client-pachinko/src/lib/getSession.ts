import { cookies, headers } from "next/headers";

import { API_ROUTES } from "@/config/routes.config";
import { User } from "@/types/user.types";

export type GetSessionType = { user: User | null };

export async function getSession(): Promise<GetSessionType> {
    // 外部API無効化 - 韓国ログインセッションのみチェック
    const sessionId = (await cookies()).get("user.sid");
    
    if (!sessionId) return { user: null }
    
    // 韓国セッションがあれば完全なUser型オブジェクトを返す
    const mockKoreaUser: User = {
        id: 1,
        loginId: "korea_user_1",
        siteId: 1,
        agencyId: 1,
        referral: 0,
        userCode: "korea_user_1",
        info: {
            ip: "127.0.0.1",
            os: {},
            exp: 100,
            needExp: 200,
            spin: 3,
            level: 1,
            phone: "+82-10-1234-5678",
            client: {
                name: "korea-client",
                type: "web",
                version: "1.0.0"
            },
            device: {
                id: "korea-device",
                type: "desktop",
                brand: "chrome",
                model: "browser"
            },
            nickname: "testuser1",
            isApprove: "approved",
            sessionId: "korea_session",
            transaction: {
                bank: "KB국민은행",
                bankNumber: "123456789",
                realname: "테스트 사용자",
                withdrawalType: "bank"
            },
            nextLevelData: {
                bonus: 1000,
                needExp: 200,
                name: "Level 2"
            },
            curLevelData: {
                bonus: 500,
                needExp: 100,
                name: "Level 1"
            }
        },
        rollingCommission: {
            games: {
                slot: 0.5,
                casino: 0.3
            },
            isUse: true
        },
        bonus: {
            locked: 0,
            unlocked: 1000
        },
        attendance: {
            dates: [],
            totalReward: 0,
            streakReward: 0,
            count: 0,
            total: 0,
            streakDays: 0
        },
        isUse: true,
        roulette: {
            count: 3,
            total: 10
        },
        wallets: {
            money: 50000,
            vault: 0
        },
        rolling: {
            games: {
                slot: 0,
                casino: 0,
                holdem: 0,
                sports: 0,
                virtual: 0,
                pachinko: 0
            },
            isUse: false
        },
        losingCommission: {
            games: {
                slot: 0,
                casino: 0
            },
            isUse: false
        },
        eventBans: [],
        gameBans: []
    };
    
    return { user: mockKoreaUser };
}