import { SiteSettingsResponse } from "@/config/settings.config";

export const settingsApi = {
    siteSettings: async (): Promise<SiteSettingsResponse> => {
        // 外部API無効化 - ローカルモックデータを返す
        return {
            site: {
                logo: {
                    icon: '/logo.png',
                    logo: '/logo.png',
                    light: {
                        icon: '/logo.png',
                        title: '/logo.png'
                    },
                    dark: {
                        icon: '/logo.png',
                        title: '/logo.png'
                    }
                },
                info: {
                    nickname: 'NET8 Korea'
                },
                favicon: '/favicon.ico'
            },
            games: []
        } as any;
    }
}