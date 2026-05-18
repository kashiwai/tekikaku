#!/usr/bin/env bun
/**
 * 画像一括アップロード
 * 使い方: bun wp/bulk-upload-images.ts <ディレクトリ or ファイルパス...>
 *
 * 例:
 *   bun wp/bulk-upload-images.ts ./images/
 *   bun wp/bulk-upload-images.ts img1.jpg img2.png
 */

import { readdirSync, statSync } from "fs";
import { resolve, extname } from "path";
import { execSync } from "child_process";

const WP = resolve(import.meta.dir, "client.ts");
const IMAGE_EXTS = new Set([".jpg", ".jpeg", ".png", ".gif", ".webp", ".svg"]);
const PROJ_ROOT = resolve(import.meta.dir, "..");

function wp(args: string): string {
  return execSync(`bun ${WP} ${args}`, { encoding: "utf-8", cwd: PROJ_ROOT });
}

function collectFiles(inputPath: string): string[] {
  const abs = resolve(inputPath);
  const stat = statSync(abs);
  if (stat.isDirectory()) {
    return readdirSync(abs)
      .filter(f => IMAGE_EXTS.has(extname(f).toLowerCase()))
      .map(f => resolve(abs, f))
      .sort();
  }
  if (IMAGE_EXTS.has(extname(abs).toLowerCase())) return [abs];
  return [];
}

const inputs = process.argv.slice(2);
if (!inputs.length) {
  console.error("使い方: bun wp/bulk-upload-images.ts <ディレクトリ or ファイル...>");
  process.exit(1);
}

const files = inputs.flatMap(collectFiles);
if (!files.length) {
  console.error("アップロード対象の画像ファイルが見つかりません。");
  process.exit(1);
}

console.log(`${files.length}件をアップロードします...\n`);

const results: { file: string; url: string }[] = [];

for (const file of files) {
  try {
    const out = wp(`media upload "${file}"`);
    const urlMatch = out.match(/https?:\/\/\S+/);
    const url = urlMatch ? urlMatch[0] : "URL取得失敗";
    console.log(`OK: ${file.split("/").pop()} → ${url}`);
    results.push({ file, url });
  } catch (e: any) {
    console.error(`FAIL: ${file.split("/").pop()} → ${e.message}`);
  }
}

console.log(`\n完了: ${results.length}/${files.length}件アップロード成功`);
