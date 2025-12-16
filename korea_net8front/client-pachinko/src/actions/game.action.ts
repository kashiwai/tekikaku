"use server";
import { API_ROUTES } from "@/config/routes.config";
import fetcher from "@/lib/fetcher";

import { getCookieHeader } from "./cookie.actions";
import { GameLaunchResponse } from "@/types/game.types";

export async function getGameLaunch({ id }: { id: string }): Promise<GameLaunchResponse> {
    const res = await fetcher<GameLaunchResponse>(API_ROUTES.GAME.LAUNCH, {
        method: "POST",
        body: JSON.stringify({
            id: decodeURIComponent(id),
        }),
        headers: {
            Cookie: await getCookieHeader(),
        },
        next: { tags: ["game-launch"] }
    }, true);

    console.log(res);

    if (!res.success) return { link: null, title: '', api: '', type: '' }
    return res.data;
}
