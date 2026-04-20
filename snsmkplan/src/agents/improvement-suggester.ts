import Anthropic from "@anthropic-ai/sdk";
import { readFileSync, writeFileSync, existsSync, mkdirSync } from "fs";
import { join } from "path";
import type { PostResult, PerformanceMetrics } from "../config/types.ts";
import { notifyImprovementReport } from "../notification/slack.ts";

const client = new Anthropic();
const REPORTS_DIR = process.env.OUTPUT_DIR ? join(process.env.OUTPUT_DIR, "reports") : "./output/reports";

export async function analyzeAndSuggest(postResults: PostResult[]): Promise<void> {
  mkdirSync(REPORTS_DIR, { recursive: true });

  console.log("[改善提案] パフォーマンスを分析中...");

  const metrics = await fetchMetrics(postResults);
  const suggestions = await generateSuggestions(metrics);

  const report = {
    analyzed_at: new Date().toISOString(),
    metrics,
    suggestions,
  };

  const reportPath = join(REPORTS_DIR, `report_${Date.now()}.json`);
  writeFileSync(reportPath, JSON.stringify(report, null, 2));

  await notifyImprovementReport(metrics, suggestions);
  console.log("[改善提案] Slackに送信完了");
}

async function fetchMetrics(postResults: PostResult[]): Promise<PerformanceMetrics[]> {
  const metrics: PerformanceMetrics[] = [];

  for (const post of postResults) {
    if (post.platform === "instagram") {
      const metric = await fetchInstagramMetrics(post.post_id);
      if (metric) metrics.push(metric);
    } else if (post.platform === "tiktok") {
      const metric = await fetchTikTokMetrics(post.post_id);
      if (metric) metrics.push(metric);
    }
  }

  return metrics;
}

async function fetchInstagramMetrics(postId: string): Promise<PerformanceMetrics | null> {
  const accessToken = process.env.INSTAGRAM_ACCESS_TOKEN ?? "";
  if (!accessToken) return null;

  try {
    const res = await fetch(
      `https://graph.facebook.com/v21.0/${postId}/insights?metric=plays,likes,comments,shares,profile_visits,ig_reels_video_view_total_time&access_token=${accessToken}`,
    );
    if (!res.ok) return null;
    const data = await res.json() as { data: Array<{ name: string; values: Array<{ value: number }> }> };

    const getValue = (name: string) =>
      data.data.find((d) => d.name === name)?.values?.[0]?.value ?? 0;

    return {
      platform: "instagram",
      post_id: postId,
      views: getValue("plays"),
      likes: getValue("likes"),
      comments: getValue("comments"),
      shares: getValue("shares"),
      profile_visits: getValue("profile_visits"),
      link_clicks: 0,
      measured_at: new Date().toISOString(),
    };
  } catch {
    return null;
  }
}

async function fetchTikTokMetrics(publishId: string): Promise<PerformanceMetrics | null> {
  const accessToken = process.env.TIKTOK_ACCESS_TOKEN ?? "";
  if (!accessToken) return null;

  try {
    const res = await fetch("https://open.tiktokapis.com/v2/video/query/", {
      method: "POST",
      headers: {
        Authorization: `Bearer ${accessToken}`,
        "Content-Type": "application/json; charset=UTF-8",
      },
      body: JSON.stringify({
        filters: { video_ids: [publishId] },
        fields: ["play_count", "like_count", "comment_count", "share_count", "view_count"],
      }),
    });

    if (!res.ok) return null;
    const data = await res.json() as { data: { videos: Array<{ play_count: number; like_count: number; comment_count: number; share_count: number }> } };
    const v = data.data.videos[0];
    if (!v) return null;

    return {
      platform: "tiktok",
      post_id: publishId,
      views: v.play_count,
      likes: v.like_count,
      comments: v.comment_count,
      shares: v.share_count,
      profile_visits: 0,
      link_clicks: 0,
      measured_at: new Date().toISOString(),
    };
  } catch {
    return null;
  }
}

async function generateSuggestions(metrics: PerformanceMetrics[]): Promise<string[]> {
  if (metrics.length === 0) {
    return ["データがまだありません。投稿後24時間後に再分析することをお勧めします。"];
  }

  const metricsText = metrics
    .map(
      (m) =>
        `${m.platform}: 再生${m.views} / いいね${m.likes} / コメント${m.comments} / シェア${m.shares} / プロフ訪問${m.profile_visits}`,
    )
    .join("\n");

  const response = await client.messages.create({
    model: "claude-sonnet-4-6",
    max_tokens: 1000,
    system: "あなたはSNSマーケティングのアナリストです。データをもとに具体的な改善提案をしてください。",
    messages: [
      {
        role: "user",
        content: `以下のInstagram/TikTokの投稿パフォーマンスを分析し、改善提案を3〜5個出してください。

${metricsText}

JSON配列で出力してください:
["提案1", "提案2", "提案3"]`,
      },
    ],
  });

  const firstContent = response.content[0];
  const text = firstContent?.type === "text" ? firstContent.text : "";
  const jsonMatch = text.match(/\[[\s\S]*\]/);
  if (!jsonMatch) return ["分析完了。次回の投稿でA/Bテストを試してください。"];

  return JSON.parse(jsonMatch[0]) as string[];
}
