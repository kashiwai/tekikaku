#!/usr/bin/env bun
/**
 * 投稿作成ヘルパー
 * 使い方: bun wp/post.ts
 *
 * このスクリプトを直接編集して投稿内容を記入し、実行してください。
 * または Claude Code から直接内容を渡して実行させることができます。
 */

import { execSync } from "child_process";
import { resolve } from "path";

const WP = resolve(import.meta.dir, "client.ts");

function wp(args: string, stdin?: string): string {
  const cmd = `bun ${WP} ${args}`;
  if (stdin) {
    const result = execSync(cmd, {
      input: stdin,
      encoding: "utf-8",
      cwd: resolve(import.meta.dir, ".."),
    });
    return result;
  }
  return execSync(cmd, { encoding: "utf-8", cwd: resolve(import.meta.dir, "..") });
}

// ---- ここから投稿内容を設定 ----

const post = {
  title: "サンプル投稿タイトル",
  content: `
<p>ここに本文を書きます。HTMLタグが使えます。</p>

<h2>見出し2</h2>
<p>段落テキスト。</p>

<ul>
  <li>リスト1</li>
  <li>リスト2</li>
</ul>
  `.trim(),
  status: "draft", // "draft" | "publish" | "private"
  categories: [] as number[], // カテゴリIDを配列で指定
  tags: [] as number[], // タグIDを配列で指定
  excerpt: "", // 抜粋（省略可）
  // featured_media: 0, // アイキャッチ画像のメディアID
};

// ---- ここまで ----

console.log("投稿を作成中...");
const result = wp("posts create", JSON.stringify(post));
console.log(result);
