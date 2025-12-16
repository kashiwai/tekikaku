import { SiteSettingsResponse } from "@/config/settings.config";
import { BanType, User } from "@/types/user.types";

export const guardUtils = {
    isGameBanned: (settings: SiteSettingsResponse, type: BanType, user: User | null)=> {
        if(!settings) return {
            site: false,
            user: false,
        };

        const isSiteBanned = settings.games?.some((banned) => banned.type === type && !banned.isUse) || false;
        const isUserBanned = user?.gameBans?.some((banned) => banned.type === type) || false;

        return {
            site: isSiteBanned,
            user: isUserBanned
        }
    }
}