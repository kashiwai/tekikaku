import "dotenv/config";
import { runPipeline, runAnalysis } from "./src/pipeline.ts";

const command = process.argv[2] ?? "run";

switch (command) {
  case "run":
    await runPipeline();
    break;
  case "analyze":
    await runAnalysis();
    break;
  case "schedule":
    await import("./src/scheduler.ts");
    break;
  case "dry-run":
    process.env.DRY_RUN = "true";
    await runPipeline(1);
    break;
  default:
    console.log(`使い方:
  bun run index.ts run        # 今すぐ動画生成・投稿
  bun run index.ts dry-run    # テスト実行（投稿なし）
  bun run index.ts analyze    # 改善分析・Slack通知
  bun run index.ts schedule   # スケジューラー起動`);
}