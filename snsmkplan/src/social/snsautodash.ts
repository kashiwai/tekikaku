import { readFileSync } from "fs";
import { extname } from "path";
import type { PostResult } from "../config/types.ts";

const BASE_URL = process.env.SNSAUTODASH_URL ?? "https://snsautodash.biz";
const EMAIL = process.env.SNSAUTODASH_EMAIL ?? "";
const PASSWORD = process.env.SNSAUTODASH_PASSWORD ?? "";

let sessionCookie: string | null = null;

async function login(): Promise<string> {
  const res = await fetch(`${BASE_URL}/api/tenant/login`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ email: EMAIL, password: PASSWORD }),
    redirect: "follow",
  });
  if (!res.ok) throw new Error(`snsautodash login failed: ${res.status}`);

  const setCookie = res.headers.get("set-cookie") ?? "";
  const match = setCookie.match(/sns_tenant_session=([^;]+)/);
  if (!match) throw new Error("snsautodash: session cookie not found in login response");
  return match[1]!;
}

async function getSession(): Promise<string> {
  if (!EMAIL || !PASSWORD) throw new Error("SNSAUTODASH_EMAIL / SNSAUTODASH_PASSWORD が未設定です");
  if (!sessionCookie) {
    sessionCookie = await login();
  }
  return sessionCookie;
}

async function trpcMutation<T>(procedure: string, input: unknown): Promise<T> {
  const cookie = await getSession();
  const res = await fetch(`${BASE_URL}/api/trpc/${procedure}`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Cookie: `sns_tenant_session=${cookie}`,
    },
    body: JSON.stringify({ json: input }),
  });

  if (res.status === 401) {
    // セッション切れ → 再ログイン
    sessionCookie = null;
    const newCookie = await getSession();
    const retry = await fetch(`${BASE_URL}/api/trpc/${procedure}`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Cookie: `sns_tenant_session=${newCookie}`,
      },
      body: JSON.stringify({ json: input }),
    });
    if (!retry.ok) throw new Error(`snsautodash trpc error: ${retry.status}`);
    const retryData = await retry.json() as { result?: { data?: { json: T } }; error?: { json?: { message?: string } } };
    if (retryData.error) throw new Error(retryData.error.json?.message ?? "tRPC error");
    return retryData.result!.data!.json;
  }

  if (!res.ok) throw new Error(`snsautodash trpc error: ${res.status}`);
  const data = await res.json() as { result?: { data?: { json: T } }; error?: { json?: { message?: string } } };
  if (data.error) throw new Error(data.error.json?.message ?? "tRPC error");
  return data.result!.data!.json;
}

const VIDEO_MIME_TYPES = ["video/mp4", "video/quicktime", "video/webm"];

function getMimeType(filePath: string): string {
  const ext = extname(filePath).toLowerCase();
  const map: Record<string, string> = {
    ".mp4": "video/mp4",
    ".mov": "video/quicktime",
    ".webm": "video/webm",
    ".jpg": "image/jpeg",
    ".jpeg": "image/jpeg",
    ".png": "image/png",
  };
  return map[ext] ?? "application/octet-stream";
}

interface UploadResult {
  id: number;
  status: string;
}

export async function postViaSnsautodash(
  videoPath: string,
  caption: string,
  hashtags: string,
  platforms: Array<"instagram" | "tiktok" | "x">,
  scheduledAt?: string,
): Promise<PostResult[]> {
  console.log("[snsautodash] ファイルをBase64エンコード中...");
  const fileBuffer = readFileSync(videoPath);
  const fileBase64 = fileBuffer.toString("base64");
  const fileName = videoPath.split("/").pop() ?? "video.mp4";
  const mimeType = getMimeType(videoPath);
  const isVideo = VIDEO_MIME_TYPES.includes(mimeType);

  console.log(`[snsautodash] アップロード中... (${(fileBuffer.length / 1024 / 1024).toFixed(1)} MB)`);
  const uploadResult = await trpcMutation<UploadResult>("mediaUpload.upload", {
    fileBase64,
    fileName,
    mimeType,
    fileSizeBytes: fileBuffer.length,
    mediaType: isVideo ? "video" : "image",
    caption: caption || undefined,
    hashtags: hashtags || undefined,
    platforms,
    scheduledAt: scheduledAt ?? undefined,
  });

  console.log(`[snsautodash] アップロード完了 (id: ${uploadResult.id})`);

  if (!scheduledAt) {
    console.log("[snsautodash] 今すぐ投稿...");
    await trpcMutation("mediaUpload.postNow", { id: uploadResult.id });
    console.log("[snsautodash] 投稿完了");
  }

  return platforms.map((platform) => ({
    platform: platform === "x" ? "instagram" : platform,
    post_id: String(uploadResult.id),
    created_at: new Date().toISOString(),
  }));
}
