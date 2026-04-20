import { readFileSync } from "fs";
import type { PostResult } from "../config/types.ts";

const BASE_URL = "https://graph.facebook.com/v21.0";

export async function postToInstagram(
  videoPath: string,
  caption: string,
): Promise<PostResult> {
  const accessToken = process.env.INSTAGRAM_ACCESS_TOKEN ?? "";
  const accountId = process.env.INSTAGRAM_ACCOUNT_ID ?? "";

  if (!accessToken || !accountId) {
    throw new Error("INSTAGRAM_ACCESS_TOKEN または INSTAGRAM_ACCOUNT_ID が未設定です");
  }

  console.log("[Instagram] 動画をアップロード中...");

  // Step 1: コンテナ作成
  const containerRes = await fetch(`${BASE_URL}/${accountId}/media`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      media_type: "REELS",
      video_url: await uploadVideoToServer(videoPath),
      caption,
      share_to_feed: true,
      access_token: accessToken,
    }),
  });

  if (!containerRes.ok) {
    const err = await containerRes.text();
    throw new Error(`Instagram コンテナ作成エラー: ${err}`);
  }

  const container = await containerRes.json() as { id: string };

  // Step 2: アップロード完了待機
  await waitForInstagramUpload(container.id, accessToken);

  // Step 3: 投稿
  const publishRes = await fetch(`${BASE_URL}/${accountId}/media_publish`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      creation_id: container.id,
      access_token: accessToken,
    }),
  });

  if (!publishRes.ok) {
    const err = await publishRes.text();
    throw new Error(`Instagram 投稿エラー: ${err}`);
  }

  const published = await publishRes.json() as { id: string };
  console.log(`[Instagram] 投稿完了: ${published.id}`);

  return {
    platform: "instagram",
    post_id: published.id,
    url: `https://www.instagram.com/reel/${published.id}/`,
    created_at: new Date().toISOString(),
  };
}

async function waitForInstagramUpload(containerId: string, accessToken: string): Promise<void> {
  const maxAttempts = 30;
  for (let i = 0; i < maxAttempts; i++) {
    await new Promise((r) => setTimeout(r, 5000));
    const res = await fetch(
      `${BASE_URL}/${containerId}?fields=status_code&access_token=${accessToken}`,
    );
    const data = await res.json() as { status_code: string };
    if (data.status_code === "FINISHED") return;
    if (data.status_code === "ERROR") throw new Error("Instagram 動画処理エラー");
    console.log(`[Instagram] アップロード待機中... (${data.status_code})`);
  }
  throw new Error("Instagram アップロードタイムアウト");
}

async function uploadVideoToServer(videoPath: string): Promise<string> {
  // 実際の運用ではS3やCloudflare R2などにアップロードして公開URLを返す
  // ここではEnvironment変数で設定されたベースURLを使用
  const uploadUrl = process.env.VIDEO_UPLOAD_URL ?? "";
  if (!uploadUrl) {
    throw new Error("VIDEO_UPLOAD_URL が未設定です（動画をS3等にアップロードするURLが必要）");
  }

  const videoData = readFileSync(videoPath);
  const response = await fetch(uploadUrl, {
    method: "POST",
    headers: {
      "Content-Type": "video/mp4",
      Authorization: `Bearer ${process.env.UPLOAD_API_KEY ?? ""}`,
    },
    body: videoData,
  });

  if (!response.ok) throw new Error(`動画アップロードエラー: ${response.status}`);
  const result = await response.json() as { url: string };
  return result.url;
}
