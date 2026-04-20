import { readFileSync } from "fs";
import type { PostResult } from "../config/types.ts";

const API_BASE = process.env.MANUS_API_BASE ?? "https://api.manus.ai";
const API_KEY = process.env.MANUS_API_KEY ?? "";

function headers() {
  return {
    Authorization: `Bearer ${API_KEY}`,
    "Content-Type": "application/json",
  };
}

// ファイルをManusにアップロードしてfile_idを返す
async function uploadFile(filePath: string, mimeType = "video/mp4"): Promise<string> {
  // presigned URL取得
  const presignRes = await fetch(`${API_BASE}/v1/files`, {
    method: "POST",
    headers: headers(),
    body: JSON.stringify({ mime_type: mimeType }),
  });
  if (!presignRes.ok) throw new Error(`Manus file init error: ${await presignRes.text()}`);
  const { file_id, upload_url } = await presignRes.json() as { file_id: string; upload_url: string };

  // 実ファイルをアップロード
  const fileData = readFileSync(filePath);
  const uploadRes = await fetch(upload_url, {
    method: "PUT",
    headers: { "Content-Type": mimeType },
    body: fileData,
  });
  if (!uploadRes.ok) throw new Error(`Manus file upload error: ${uploadRes.status}`);

  return file_id;
}

// タスク完了までポーリング
async function waitForTask(taskId: string, timeoutMs = 300000): Promise<string> {
  const start = Date.now();
  while (Date.now() - start < timeoutMs) {
    await new Promise((r) => setTimeout(r, 5000));
    const res = await fetch(`${API_BASE}/v1/tasks/${taskId}`, { headers: headers() });
    if (!res.ok) throw new Error(`Manus task poll error: ${res.status}`);
    const task = await res.json() as { status: string; result?: string; error?: string };

    if (task.status === "completed") return task.result ?? "";
    if (task.status === "failed") throw new Error(`Manus task failed: ${task.error}`);
    console.log(`[Manus] タスク処理中... (${task.status})`);
  }
  throw new Error("Manus task timeout");
}

// タスクを作成して実行
async function createTask(prompt: string, fileIds: string[] = []): Promise<string> {
  const body: Record<string, unknown> = { prompt };
  if (fileIds.length > 0) body.file_ids = fileIds;

  const res = await fetch(`${API_BASE}/v1/tasks`, {
    method: "POST",
    headers: headers(),
    body: JSON.stringify(body),
  });
  if (!res.ok) throw new Error(`Manus task create error: ${await res.text()}`);
  const { task_id } = await res.json() as { task_id: string };
  return task_id;
}

export async function postViaManusInstagram(
  videoPath: string,
  caption: string,
): Promise<PostResult> {
  if (!API_KEY) throw new Error("MANUS_API_KEY が未設定です");

  console.log("[Manus] Instagram用動画をアップロード中...");
  const fileId = await uploadFile(videoPath);

  const prompt = `
Instagramにリール動画を投稿してください。

キャプション:
${caption}

手順:
1. Instagramにログインしてください（すでにログイン済みの場合はスキップ）
2. リール投稿画面を開いてください
3. アップロードされた動画ファイル（file_id: ${fileId}）を使用してください
4. 上記のキャプションをそのまま入力してください
5. 投稿を実行してください
6. 投稿URLまたは投稿IDを返してください

投稿完了後、投稿URLをresultに含めてください。
`.trim();

  console.log("[Manus] Instagramへの投稿タスクを作成中...");
  const taskId = await createTask(prompt, [fileId]);
  const result = await waitForTask(taskId);
  console.log(`[Manus→Instagram] 完了: ${result}`);

  return {
    platform: "instagram",
    post_id: taskId,
    url: result || undefined,
    created_at: new Date().toISOString(),
  };
}

export async function postViaManusX(
  videoPath: string,
  text: string,
): Promise<PostResult> {
  if (!API_KEY) throw new Error("MANUS_API_KEY が未設定です");

  console.log("[Manus] X用動画をアップロード中...");
  const fileId = await uploadFile(videoPath);

  const prompt = `
X（Twitter）に動画付きポストを投稿してください。

投稿テキスト:
${text}

手順:
1. X（Twitter）にログインしてください（すでにログイン済みの場合はスキップ）
2. 新規投稿画面を開いてください
3. アップロードされた動画ファイル（file_id: ${fileId}）を添付してください
4. 上記のテキストをそのまま入力してください
5. 投稿を実行してください
6. 投稿URLを返してください

投稿完了後、投稿URLをresultに含めてください。
`.trim();

  console.log("[Manus] Xへの投稿タスクを作成中...");
  const taskId = await createTask(prompt, [fileId]);
  const result = await waitForTask(taskId);
  console.log(`[Manus→X] 完了: ${result}`);

  return {
    platform: "instagram",
    post_id: taskId,
    url: result || undefined,
    created_at: new Date().toISOString(),
  };
}

export async function postViaManusTikTok(
  videoPath: string,
  caption: string,
): Promise<PostResult> {
  if (!API_KEY) throw new Error("MANUS_API_KEY が未設定です");

  console.log("[Manus] TikTok用動画をアップロード中...");
  const fileId = await uploadFile(videoPath);

  const prompt = `
TikTokに動画を投稿してください。

キャプション:
${caption}

手順:
1. TikTokにログインしてください（すでにログイン済みの場合はスキップ）
2. 動画投稿画面を開いてください
3. アップロードされた動画ファイル（file_id: ${fileId}）を使用してください
4. 上記のキャプションをそのまま入力してください
5. 投稿を実行してください
6. 投稿URLを返してください

投稿完了後、投稿URLをresultに含めてください。
`.trim();

  console.log("[Manus] TikTokへの投稿タスクを作成中...");
  const taskId = await createTask(prompt, [fileId]);
  const result = await waitForTask(taskId);
  console.log(`[Manus→TikTok] 完了: ${result}`);

  return {
    platform: "tiktok",
    post_id: taskId,
    url: result || undefined,
    created_at: new Date().toISOString(),
  };
}
