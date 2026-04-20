import { postViaSnsautodash } from "../social/snsautodash.ts";
import type { PostResult } from "../config/types.ts";
import type { PostTexts } from "./post-text-generator.ts";

export async function autoPost(
  videoPath: string,
  postTexts: PostTexts,
  platforms: Array<"instagram" | "tiktok" | "x"> = ["instagram", "tiktok"],
): Promise<PostResult[]> {
  try {
    const caption = postTexts.instagram;
    const hashtagMatch = caption.match(/(#\S+(\s+#\S+)*)\s*$/);
    const hashtags = hashtagMatch ? hashtagMatch[0].trim() : "";
    const captionBody = hashtagMatch ? caption.slice(0, hashtagMatch.index).trim() : caption;

    const results = await postViaSnsautodash(videoPath, captionBody, hashtags, platforms);
    return results;
  } catch (e) {
    console.error("[自動投稿] 投稿エラー:", e);
    return [];
  }
}
