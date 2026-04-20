import cron from "node-cron";
import "dotenv/config";
import { runPipeline, runAnalysis } from "./pipeline.ts";

const DAILY_CRON = process.env.DAILY_CRON ?? "0 9 * * *";
const ANALYSIS_CRON = process.env.ANALYSIS_CRON ?? "0 20 * * *";

console.log("⏰ SNS自動化スケジューラー起動");
console.log(`  動画生成: ${DAILY_CRON}`);
console.log(`  改善分析: ${ANALYSIS_CRON}`);

// 毎日の動画生成・投稿
cron.schedule(DAILY_CRON, async () => {
  console.log(`\n[${new Date().toLocaleString("ja-JP")}] 定期実行開始`);
  try {
    await runPipeline();
  } catch (e) {
    console.error("スケジュール実行エラー:", e);
  }
}, { timezone: "Asia/Tokyo" });

// 毎日の改善分析
cron.schedule(ANALYSIS_CRON, async () => {
  console.log(`\n[${new Date().toLocaleString("ja-JP")}] 改善分析開始`);
  try {
    await runAnalysis();
  } catch (e) {
    console.error("分析実行エラー:", e);
  }
}, { timezone: "Asia/Tokyo" });

console.log("スケジューラー稼働中... (Ctrl+C で停止)\n");
