"use server";

import { cache } from "react";

import { headers } from "next/headers";

import { gameConfig } from "@/config/game.config";
import { API_ROUTES } from "@/config/routes.config";
import fetcher from "@/lib/fetcher";
import { GameItem, GameResponseType } from "@/types/game.types";


const _getGameList = async ({
  game = "",
  type = "",
  title = "",
  vendor = "",
  page = 1,
  limit = gameConfig.pagination.limit,
}: {
  game: string; // casino, slot, minigame
  type: string;
  title?: string;
  vendor?: string;
  page?: number;
  limit?: number;
}) => {
  const cookie = (await headers()).get('cookie') || '';

  let query = `${API_ROUTES.GAME.LIST}?page=${page}&limit=${limit}`;

  if (game !== "") query += `&game=${game}`;
  if (type !== "") query += `&type=${type}`;
  // if (provider !== "") query += `&provider=${provider}`;
  if (title !== "") query += `&title=${title}`;
  if (vendor !== "") query += `&vendor=${vendor}`;
  
  const res = await fetcher<GameResponseType>(query, {
    method: "GET",
    headers: {
      cookie
    }
  } );

  if (!res.success) {
    return { list: [], total: 0, vendors: [], types: [] };
  }
  return res.data
};

// ✅ Export memoized version
export const getGameList = cache(_getGameList);

const _getSearchGameList = async (
  type: string,
  provider: string,
  title: string,
  page: number,
  limit: number
) => {
  const cookie = (await headers()).get('cookie') || '';
  let query = `${API_ROUTES.GAME.LIST}?page=${page}&limit=${limit}`;

  if (type !== "all") query += `&type=${type}`;
  if (provider !== "") query += `&provider=${provider}`;
  if (title !== "") query += `&title=${title}`;

  const res = await fetcher<GameResponseType>(query, {
    method: "GET",
    headers: {
      cookie
    }
  });

  if (!res.success) {
    return { data: [] as GameItem[], pagination: null, providers: [] };
  }

  return {
    data: res.data.list,
    pagination: res.data.pagination,
    providers: res.data.providers,
  };
};

// ✅ Export memoized version
export const getSearchGameList = cache(_getSearchGameList);

// export async function getGameLaunch(game_code: string) {
//   const cookie = (await headers()).get('cookie') || '';

  
//   const res = await SSRFetcher(API_ROUTES.GAME.LAUNCH, {
//     method: "POST",
//     body: JSON.stringify({
//       game_code
//     }),
//     headers: {
//       cookie
//     },
//     next: { tags: ["game-launch"] }
//   });

//   if (res.success) return {
//     data: { url: res.data.url, name: res.data.name }, success: true
//   }

//   return {
//     message: res.message,
//     sucess: false
//   }
// }