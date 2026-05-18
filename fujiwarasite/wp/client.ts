#!/usr/bin/env bun
/**
 * WordPress REST API クライアント
 * 使い方: bun wp/client.ts <command> [options]
 *
 * コマンド一覧:
 *   posts list                    投稿一覧
 *   posts get <id>                投稿取得
 *   posts create                  投稿作成 (stdin からJSON)
 *   posts update <id>             投稿更新 (stdin からJSON)
 *   posts delete <id>             投稿削除
 *   pages list                    ページ一覧
 *   pages create                  ページ作成 (stdin からJSON)
 *   pages update <id>             ページ更新 (stdin からJSON)
 *   media list                    メディア一覧
 *   media upload <file>           ファイルアップロード
 *   plugins list                  プラグイン一覧
 *   plugins activate <plugin>     プラグイン有効化
 *   plugins deactivate <plugin>   プラグイン無効化
 *   themes list                   テーマ一覧
 *   themes activate <theme>       テーマ適用
 *   categories list               カテゴリ一覧
 *   categories create             カテゴリ作成 (stdin からJSON)
 *   tags list                     タグ一覧
 *   tags create                   タグ作成 (stdin からJSON)
 *   users list                    ユーザー一覧
 *   settings get                  サイト設定取得
 *   search <keyword>              投稿検索
 */

import { readFileSync } from "fs";
import { resolve } from "path";

// .env 読み込み
function loadEnv() {
  const envPath = resolve(import.meta.dir, "../.env");
  try {
    const content = readFileSync(envPath, "utf-8");
    for (const line of content.split("\n")) {
      const trimmed = line.trim();
      if (!trimmed || trimmed.startsWith("#")) continue;
      const [key, ...rest] = trimmed.split("=");
      if (key && rest.length > 0) process.env[key] = rest.join("=");
    }
  } catch {
    console.error("ERROR: .env ファイルが見つかりません。.env.example をコピーして設定してください。");
    process.exit(1);
  }
}

loadEnv();

const WP_URL = (process.env.WP_URL || "").replace(/\/$/, "");
const WP_USER = process.env.WP_USER || "";
const WP_APP_PASSWORD = process.env.WP_APP_PASSWORD || "";

if (!WP_URL || !WP_USER || !WP_APP_PASSWORD) {
  console.error("ERROR: .env に WP_URL, WP_USER, WP_APP_PASSWORD を設定してください。");
  process.exit(1);
}

const AUTH = Buffer.from(`${WP_USER}:${WP_APP_PASSWORD}`).toString("base64");
const BASE = `${WP_URL}/wp-json/wp/v2`;

async function api(path: string, options: RequestInit = {}) {
  const url = `${BASE}${path}`;
  const res = await fetch(url, {
    ...options,
    headers: {
      Authorization: `Basic ${AUTH}`,
      "Content-Type": "application/json",
      ...(options.headers || {}),
    },
  });

  const text = await res.text();
  if (!res.ok) {
    console.error(`HTTP ${res.status}: ${text}`);
    process.exit(1);
  }

  try {
    return JSON.parse(text);
  } catch {
    return text;
  }
}

async function readStdin(): Promise<any> {
  const chunks: Buffer[] = [];
  for await (const chunk of process.stdin) chunks.push(chunk);
  return JSON.parse(Buffer.concat(chunks).toString());
}

async function uploadMedia(filePath: string) {
  const absPath = resolve(filePath);
  const file = readFileSync(absPath);
  const filename = absPath.split("/").pop()!;
  const ext = filename.split(".").pop()?.toLowerCase() || "";
  const mimeTypes: Record<string, string> = {
    jpg: "image/jpeg", jpeg: "image/jpeg", png: "image/png",
    gif: "image/gif", webp: "image/webp", svg: "image/svg+xml",
    pdf: "application/pdf", mp4: "video/mp4",
  };
  const mime = mimeTypes[ext] || "application/octet-stream";

  const url = `${BASE}/media`;
  const res = await fetch(url, {
    method: "POST",
    headers: {
      Authorization: `Basic ${AUTH}`,
      "Content-Disposition": `attachment; filename="${filename}"`,
      "Content-Type": mime,
    },
    body: file,
  });
  const text = await res.text();
  if (!res.ok) { console.error(`HTTP ${res.status}: ${text}`); process.exit(1); }
  return JSON.parse(text);
}

const [, , resource, action, ...rest] = process.argv;

const cmd = `${resource} ${action}`.trim();

(async () => {
  switch (cmd) {
    // 投稿
    case "posts list": {
      const params = new URLSearchParams({ per_page: "20", orderby: "date", order: "desc" });
      if (rest[0]) params.set("search", rest[0]);
      const posts = await api(`/posts?${params}`);
      for (const p of posts) console.log(`[${p.id}] ${p.status} | ${p.title.rendered} | ${p.link}`);
      break;
    }
    case "posts get": {
      const p = await api(`/posts/${rest[0]}`);
      console.log(JSON.stringify(p, null, 2));
      break;
    }
    case "posts create": {
      const body = await readStdin();
      const p = await api("/posts", { method: "POST", body: JSON.stringify(body) });
      console.log(`作成完了: [${p.id}] ${p.link}`);
      break;
    }
    case "posts update": {
      const body = await readStdin();
      const p = await api(`/posts/${rest[0]}`, { method: "POST", body: JSON.stringify(body) });
      console.log(`更新完了: [${p.id}] ${p.link}`);
      break;
    }
    case "posts delete": {
      await api(`/posts/${rest[0]}`, { method: "DELETE", body: JSON.stringify({ force: true }) });
      console.log(`削除完了: ID ${rest[0]}`);
      break;
    }

    // ページ
    case "pages list": {
      const pages = await api("/pages?per_page=50&orderby=title&order=asc");
      for (const p of pages) console.log(`[${p.id}] ${p.status} | ${p.title.rendered} | ${p.link}`);
      break;
    }
    case "pages get": {
      const p = await api(`/pages/${rest[0]}`);
      console.log(JSON.stringify(p, null, 2));
      break;
    }
    case "pages create": {
      const body = await readStdin();
      const p = await api("/pages", { method: "POST", body: JSON.stringify(body) });
      console.log(`作成完了: [${p.id}] ${p.link}`);
      break;
    }
    case "pages update": {
      const body = await readStdin();
      const p = await api(`/pages/${rest[0]}`, { method: "POST", body: JSON.stringify(body) });
      console.log(`更新完了: [${p.id}] ${p.link}`);
      break;
    }

    // メディア
    case "media list": {
      const items = await api("/media?per_page=20&orderby=date&order=desc");
      for (const m of items) console.log(`[${m.id}] ${m.media_type} | ${m.title.rendered} | ${m.source_url}`);
      break;
    }
    case "media upload": {
      if (!rest[0]) { console.error("使い方: media upload <ファイルパス>"); process.exit(1); }
      const m = await uploadMedia(rest[0]);
      console.log(`アップロード完了: [${m.id}] ${m.source_url}`);
      break;
    }

    // プラグイン
    case "plugins list": {
      const plugins = await api("/plugins?per_page=100");
      for (const p of plugins) console.log(`[${p.status}] ${p.name} (${p.plugin})`);
      break;
    }
    case "plugins activate": {
      const p = await api(`/plugins/${encodeURIComponent(rest[0])}`, {
        method: "POST", body: JSON.stringify({ status: "active" })
      });
      console.log(`有効化完了: ${p.name}`);
      break;
    }
    case "plugins deactivate": {
      const p = await api(`/plugins/${encodeURIComponent(rest[0])}`, {
        method: "POST", body: JSON.stringify({ status: "inactive" })
      });
      console.log(`無効化完了: ${p.name}`);
      break;
    }

    // テーマ
    case "themes list": {
      const themes = await api("/themes?per_page=100");
      for (const t of themes) console.log(`[${t.status}] ${t.name.rendered} (${t.stylesheet})`);
      break;
    }
    case "themes activate": {
      const t = await api(`/themes/${encodeURIComponent(rest[0])}`, {
        method: "POST", body: JSON.stringify({ status: "active" })
      });
      console.log(`テーマ適用完了: ${t.name.rendered}`);
      break;
    }

    // カテゴリ
    case "categories list": {
      const cats = await api("/categories?per_page=100");
      for (const c of cats) console.log(`[${c.id}] ${c.name} (${c.count}件)`);
      break;
    }
    case "categories create": {
      const body = await readStdin();
      const c = await api("/categories", { method: "POST", body: JSON.stringify(body) });
      console.log(`作成完了: [${c.id}] ${c.name}`);
      break;
    }

    // タグ
    case "tags list": {
      const tags = await api("/tags?per_page=100");
      for (const t of tags) console.log(`[${t.id}] ${t.name} (${t.count}件)`);
      break;
    }
    case "tags create": {
      const body = await readStdin();
      const t = await api("/tags", { method: "POST", body: JSON.stringify(body) });
      console.log(`作成完了: [${t.id}] ${t.name}`);
      break;
    }

    // ユーザー
    case "users list": {
      const users = await api("/users?per_page=100");
      for (const u of users) console.log(`[${u.id}] ${u.name} (${u.slug}) - ${u.roles?.join(", ")}`);
      break;
    }

    // サイト設定
    case "settings get": {
      const s = await api("/settings");
      console.log(JSON.stringify(s, null, 2));
      break;
    }

    // 検索
    case "search undefined":
    case "search ": {
      console.error("使い方: bun wp/client.ts search <キーワード>");
      break;
    }

    default: {
      if (resource === "search") {
        const q = [action, ...rest].filter(Boolean).join(" ");
        const results = await api(`/search?search=${encodeURIComponent(q)}&per_page=20`);
        for (const r of results) console.log(`[${r.id}] ${r.type} | ${r.title} | ${r.url}`);
      } else {
        console.error(`不明なコマンド: ${cmd}`);
        console.error("bun wp/client.ts --help で使い方を確認してください。");
        process.exit(1);
      }
    }
  }
})();
