import { postToInstagram } from "../social/instagram.ts";
import { postToTikTok } from "../social/tiktok.ts";
import type { PostResult } from "../config/types.ts";
import type { PostTexts } from "./post-text-generator.ts";

export async function autoPost(
  videoPath: string,
  postTexts: PostTexts,
  platforms: Array<"instagram" | "tiktok"> = ["instagram", "tiktok"],
): Promise<PostResult[]> {
  const results: PostResult[] = [];

  for (const platform of platforms) {
    try {
      let result: PostResult;
      if (platform === "instagram") {
        result = await postToInstagram(videoPath, postTexts.instagram);
      } else {
        result = await postToTikTok(videoPath, postTexts.tiktok);
      }
      results.push(result);
      await new Promise((r) => setTimeout(r, 3000));
    } catch (e) {
      console.error(`[自動投稿] ${platform} 投稿エラー:`, e);
    }
  }

  return results;
}
