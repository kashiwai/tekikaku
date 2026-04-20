import { readFileSync, statSync } from "fs";
import type { PostResult } from "../config/types.ts";

export async function postToTikTok(
  videoPath: string,
  caption: string,
): Promise<PostResult> {
  const accessToken = process.env.TIKTOK_ACCESS_TOKEN ?? "";
  const openId = process.env.TIKTOK_OPEN_ID ?? "";

  if (!accessToken || !openId) {
    throw new Error("TIKTOK_ACCESS_TOKEN または TIKTOK_OPEN_ID が未設定です");
  }

  console.log("[TikTok] 動画をアップロード中...");

  const videoSize = statSync(videoPath).size;
  const videoData = readFileSync(videoPath);

  // Step 1: アップロードURL取得
  const initRes = await fetch("https://open.tiktokapis.com/v2/post/publish/video/init/", {
    method: "POST",
    headers: {
      Authorization: `Bearer ${accessToken}`,
      "Content-Type": "application/json; charset=UTF-8",
    },
    body: JSON.stringify({
      post_info: {
        title: caption.slice(0, 100),
        privacy_level: "PUBLIC_TO_EVERYONE",
        disable_duet: false,
        disable_comment: false,
        disable_stitch: false,
        video_cover_timestamp_ms: 1000,
      },
      source_info: {
        source: "FILE_UPLOAD",
        video_size: videoSize,
        chunk_size: videoSize,
        total_chunk_count: 1,
      },
    }),
  });

  if (!initRes.ok) {
    const err = await initRes.text();
    throw new Error(`TikTok 初期化エラー: ${err}`);
  }

  const initData = await initRes.json() as {
    data: { publish_id: string; upload_url: string };
  };
  const { publish_id, upload_url } = initData.data;

  // Step 2: 動画アップロード
  const uploadRes = await fetch(upload_url, {
    method: "PUT",
    headers: {
      "Content-Type": "video/mp4",
      "Content-Range": `bytes 0-${videoSize - 1}/${videoSize}`,
    },
    body: videoData,
  });

  if (!uploadRes.ok) throw new Error(`TikTok アップロードエラー: ${uploadRes.status}`);

  // Step 3: ステータス確認
  await waitForTikTokPublish(publish_id, accessToken);
  console.log(`[TikTok] 投稿完了: ${publish_id}`);

  return {
    platform: "tiktok",
    post_id: publish_id,
    created_at: new Date().toISOString(),
  };
}

async function waitForTikTokPublish(publishId: string, accessToken: string): Promise<void> {
  const maxAttempts = 20;
  for (let i = 0; i < maxAttempts; i++) {
    await new Promise((r) => setTimeout(r, 5000));
    const res = await fetch("https://open.tiktokapis.com/v2/post/publish/status/fetch/", {
      method: "POST",
      headers: {
        Authorization: `Bearer ${accessToken}`,
        "Content-Type": "application/json; charset=UTF-8",
      },
      body: JSON.stringify({ publish_id: publishId }),
    });
    const data = await res.json() as { data: { status: string } };
    if (data.data.status === "PUBLISH_COMPLETE") return;
    if (data.data.status === "FAILED") throw new Error("TikTok 投稿失敗");
    console.log(`[TikTok] 処理中... (${data.data.status})`);
  }
}
