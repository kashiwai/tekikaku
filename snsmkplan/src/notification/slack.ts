import { IncomingWebhook } from "@slack/webhook";
import type { PostResult, PerformanceMetrics } from "../config/types.ts";

function getWebhook(): IncomingWebhook {
  const url = process.env.SLACK_WEBHOOK_URL ?? "";
  if (!url) throw new Error("SLACK_WEBHOOK_URL が未設定です");
  return new IncomingWebhook(url);
}

export async function notifyPostComplete(
  results: PostResult[],
  productName: string,
): Promise<void> {
  const webhook = getWebhook();
  const lines = results.map((r) => {
    const platform = r.platform === "instagram" ? ":instagram:" : ":tiktok:";
    const url = r.url ? `\n  URL: ${r.url}` : "";
    return `${platform} *${r.platform}* — 投稿ID: \`${r.post_id}\`${url}`;
  });

  await webhook.send({
    text: `✅ *投稿完了*: ${productName}`,
    attachments: [
      {
        color: "#36a64f",
        fields: [
          {
            title: "投稿結果",
            value: lines.join("\n"),
            short: false,
          },
        ],
        footer: `投稿時刻: ${new Date().toLocaleString("ja-JP")}`,
      },
    ],
  });
}

export async function notifyImprovementReport(
  metrics: PerformanceMetrics[],
  suggestions: string[],
): Promise<void> {
  const webhook = getWebhook();

  const metricsText = metrics
    .map(
      (m) =>
        `*${m.platform}* (${m.post_id.slice(0, 8)}...)\n` +
        `  再生: ${m.views.toLocaleString()} / いいね: ${m.likes} / ` +
        `プロフィール訪問: ${m.profile_visits} / リンクCT: ${m.link_clicks}`,
    )
    .join("\n\n");

  const suggestionsText = suggestions
    .map((s, i) => `${i + 1}. ${s}`)
    .join("\n");

  await webhook.send({
    text: "📊 *改善レポート*",
    attachments: [
      {
        color: "#439FE0",
        fields: [
          { title: "パフォーマンス", value: metricsText, short: false },
          { title: "改善提案", value: suggestionsText, short: false },
        ],
        footer: `分析時刻: ${new Date().toLocaleString("ja-JP")}`,
      },
    ],
  });
}

export async function notifyError(error: Error, context: string): Promise<void> {
  try {
    const webhook = getWebhook();
    await webhook.send({
      text: `❌ *エラー発生*: ${context}`,
      attachments: [
        {
          color: "#d00000",
          fields: [{ title: "エラー内容", value: error.message, short: false }],
          footer: new Date().toLocaleString("ja-JP"),
        },
      ],
    });
  } catch {
    // 通知失敗は無視
  }
}
