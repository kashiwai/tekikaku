import Anthropic from "@anthropic-ai/sdk";
import type { GeneratedScript } from "../config/types.ts";

const client = new Anthropic();

export interface PostTexts {
  instagram: string;
  tiktok: string;
}

export async function generatePostTexts(
  script: GeneratedScript,
  useAI = false,
): Promise<PostTexts> {
  if (!useAI) {
    return script.captions;
  }

  const prompt = `
以下の動画情報をもとに、Instagram用とTikTok用の投稿文を作成してください。

商品: ${script.product.name}
動画の内容: ${script.scenes.map((s, i) => `シーン${i + 1}: ${s.narration}`).join("\n")}
ハッシュタグ(Instagram): ${script.product.hashtags.instagram.join(" ")}
ハッシュタグ(TikTok): ${script.product.hashtags.tiktok.join(" ")}

以下のJSON形式で出力:
{
  "instagram": "Instagram用投稿文（絵文字含む、200文字程度、ハッシュタグ含む）",
  "tiktok": "TikTok用投稿文（短め、100文字程度、ハッシュタグ含む）"
}
`;

  const response = await client.messages.create({
    model: "claude-sonnet-4-6",
    max_tokens: 1000,
    system: "あなたはSNSマーケティングの専門家です。JSON形式で回答してください。",
    messages: [{ role: "user", content: prompt }],
  });

  const firstContent = response.content[0];
  const text = firstContent?.type === "text" ? firstContent.text : "";
  const jsonMatch = text.match(/\{[\s\S]*\}/);
  if (!jsonMatch) return script.captions;

  const parsed = JSON.parse(jsonMatch[0]) as PostTexts;
  return parsed;
}
