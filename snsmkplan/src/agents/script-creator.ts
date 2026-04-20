import Anthropic from "@anthropic-ai/sdk";
import type { Product, CaptionTemplate, GeneratedScript } from "../config/types.ts";

const client = new Anthropic();

export async function createScript(
  product: Product,
  template: CaptionTemplate,
  useAI = false,
): Promise<GeneratedScript> {
  let scenes: GeneratedScript["scenes"];

  if (useAI) {
    scenes = await generateWithAI(product, template);
  } else {
    scenes = Object.entries(template.scenes).map(([id, s]) => ({
      id: parseInt(id, 10),
      narration: s.narration.replace(/{product_name}/g, product.name),
      caption: s.caption.replace(/{product_name}/g, product.name),
    }));
  }

  const hashtagsInstagram = product.hashtags.instagram.join(" ");
  const hashtagsTiktok = product.hashtags.tiktok.join(" ");

  const captions = {
    instagram: template.caption_instagram
      .replace(/{product_name}/g, product.name)
      .replace(/{hashtags}/g, hashtagsInstagram)
      .trim(),
    tiktok: template.caption_tiktok
      .replace(/{product_name}/g, product.name)
      .replace(/{hashtags}/g, hashtagsTiktok)
      .trim(),
  };

  return {
    product,
    template,
    scenes,
    captions,
    created_at: new Date().toISOString(),
  };
}

async function generateWithAI(
  product: Product,
  template: CaptionTemplate,
): Promise<GeneratedScript["scenes"]> {
  const sceneCount = Object.keys(template.scenes).length;
  const prompt = `
あなたはショート動画の台本作成の専門家です。
以下の商品情報とテンプレートをもとに、各シーンのナレーションとテロップを作成してください。

商品情報:
- 商品名: ${product.name}
- カテゴリ: ${product.category}
- 説明: ${product.description}
- ターゲット: ${product.target_audience}
- キーワード: ${product.keywords.join(", ")}

テンプレートパターン: ${template.name}

${sceneCount}シーン分の台本を以下のJSON形式で出力してください:
{
  "scenes": [
    { "id": 1, "narration": "読み上げ用テキスト", "caption": "テロップ用テキスト（最大12文字×3行）" },
    ...
  ]
}

ルール:
- 1シーン目は必ず「スクロールを止める」フック
- 最後のシーンは必ずプロフィールリンクへのCTA
- テロップは1行最大12文字、改行は\nで表現
- ナレーションは自然な話し言葉で
- 商品名を必ず含める
`;

  const response = await client.messages.create({
    model: "claude-sonnet-4-6",
    max_tokens: 2000,
    system: "あなたはSNSマーケティングの専門家です。JSON形式で回答してください。",
    messages: [{ role: "user", content: prompt }],
  });

  const firstContent = response.content[0];
  const text = firstContent?.type === "text" ? firstContent.text : "";
  const jsonMatch = text.match(/\{[\s\S]*\}/);
  if (!jsonMatch) throw new Error("AIから有効なJSONが返されませんでした");

  const parsed = JSON.parse(jsonMatch[0]) as { scenes: GeneratedScript["scenes"] };
  return parsed.scenes;
}
