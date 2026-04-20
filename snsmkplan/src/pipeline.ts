import { join } from "path";
import { mkdirSync, writeFileSync, readFileSync, existsSync } from "fs";
import "dotenv/config";

import {
  loadProducts,
  loadSceneTemplate,
  loadCaptionTemplates,
  selectProduct,
  selectCaptionTemplate,
} from "./config/loader.ts";
import { createScript } from "./agents/script-creator.ts";
import { generateVoices } from "./agents/voice-generator.ts";
import { generatePostTexts } from "./agents/post-text-generator.ts";
import { editVideo } from "./agents/video-editor.ts";
import { autoPost } from "./agents/auto-poster.ts";
import { analyzeAndSuggest } from "./agents/improvement-suggester.ts";
import { notifyPostComplete, notifyError } from "./notification/slack.ts";
import type { PostResult } from "./config/types.ts";

const OUTPUT_DIR = process.env.OUTPUT_DIR ?? "./output";
const VIDEOS_PER_DAY = parseInt(process.env.VIDEOS_PER_DAY ?? "3", 10);
const PLATFORMS = (process.env.PLATFORMS ?? "instagram,tiktok").split(",") as Array<"instagram" | "tiktok">;
const DRY_RUN = process.env.DRY_RUN === "true";

const STATE_PATH = join(OUTPUT_DIR, "state.json");

interface State {
  used_product_ids: string[];
  used_template_ids: string[];
  post_results: PostResult[];
  last_run: string;
}

function loadState(): State {
  if (!existsSync(STATE_PATH)) {
    return { used_product_ids: [], used_template_ids: [], post_results: [], last_run: "" };
  }
  return JSON.parse(readFileSync(STATE_PATH, "utf-8")) as State;
}

function saveState(state: State): void {
  mkdirSync(OUTPUT_DIR, { recursive: true });
  writeFileSync(STATE_PATH, JSON.stringify(state, null, 2));
}

export async function runPipeline(count = VIDEOS_PER_DAY): Promise<void> {
  console.log(`\n🚀 SNS自動化パイプライン開始 (${count}本, DRY_RUN=${DRY_RUN})\n`);

  const state = loadState();
  const allPostResults: PostResult[] = [];

  try {
    const products = loadProducts();
    if (products.length === 0) throw new Error("アクティブな商品が見つかりません");

    for (let i = 0; i < count; i++) {
      console.log(`\n--- 動画 ${i + 1}/${count} ---`);

      const product = selectProduct(products, state.used_product_ids);
      const sceneTemplate = loadSceneTemplate(product.category);
      const captionTemplates = loadCaptionTemplates(product.category);
      const captionTemplate = selectCaptionTemplate(captionTemplates, state.used_template_ids);

      console.log(`[設定] 商品: ${product.name} / テンプレート: ${captionTemplate.name}`);

      // 台本作成
      const script = await createScript(product, captionTemplate);
      console.log("[台本] 作成完了");

      const runDir = join(OUTPUT_DIR, `run_${Date.now()}`);
      mkdirSync(runDir, { recursive: true });

      writeFileSync(join(runDir, "script.json"), JSON.stringify(script, null, 2));

      // 音声生成
      const audioFiles = await generateVoices(script, join(runDir, "audio"));
      console.log(`[音声] ${audioFiles.length}シーン生成完了`);

      // 投稿文生成
      const postTexts = await generatePostTexts(script);
      writeFileSync(join(runDir, "post_texts.json"), JSON.stringify(postTexts, null, 2));
      console.log("[投稿文] 生成完了");

      // 動画編集
      const videoResult = await editVideo(script, sceneTemplate, audioFiles, join(runDir, "video"));
      console.log(`[動画] 生成完了: ${videoResult.videoPath}`);

      if (!DRY_RUN) {
        // 投稿
        const results = await autoPost(videoResult.videoPath, postTexts, PLATFORMS);
        allPostResults.push(...results);

        if (results.length > 0) {
          await notifyPostComplete(results, product.name);
        }

        state.post_results.push(...results);
      } else {
        console.log("[DRY_RUN] 投稿をスキップ");
      }

      state.used_product_ids.push(product.id);
      state.used_template_ids.push(captionTemplate.id);

      // 同じ商品を全部使ったらリセット
      if (state.used_product_ids.length >= products.length) {
        state.used_product_ids = [];
      }
      if (state.used_template_ids.length >= captionTemplates.length) {
        state.used_template_ids = [];
      }
    }

    state.last_run = new Date().toISOString();
    saveState(state);

    console.log("\n✅ パイプライン完了\n");
  } catch (e) {
    const error = e instanceof Error ? e : new Error(String(e));
    console.error("❌ パイプラインエラー:", error.message);
    await notifyError(error, "メインパイプライン");
    throw error;
  }
}

export async function runAnalysis(): Promise<void> {
  console.log("\n📊 改善分析開始\n");
  const state = loadState();
  const recentPosts = state.post_results.slice(-10);
  await analyzeAndSuggest(recentPosts);
}
