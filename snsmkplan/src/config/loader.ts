import { readFileSync, readdirSync } from "fs";
import { join } from "path";
import YAML from "yaml";
import type { Product, SceneTemplate, CaptionTemplate } from "./types.ts";

const CONFIG_DIR = join(process.cwd(), "config");

export function loadProducts(): Product[] {
  const dir = join(CONFIG_DIR, "products");
  const files = readdirSync(dir).filter((f) => f.endsWith(".yaml") || f.endsWith(".yml"));
  const products: Product[] = [];
  for (const file of files) {
    const content = readFileSync(join(dir, file), "utf-8");
    const parsed = YAML.parse(content) as { products: Product[] };
    products.push(...parsed.products.filter((p) => p.active));
  }
  return products;
}

export function loadSceneTemplate(category: string): SceneTemplate {
  const dir = join(CONFIG_DIR, "scenes");
  const files = readdirSync(dir).filter((f) => f.endsWith(".yaml") || f.endsWith(".yml"));
  for (const file of files) {
    const content = readFileSync(join(dir, file), "utf-8");
    const parsed = YAML.parse(content) as { scene_template: SceneTemplate } & { scenes: SceneTemplate["scenes"]; bgm: SceneTemplate["bgm"]; narration: SceneTemplate["narration"] };
    const template: SceneTemplate = {
      ...parsed.scene_template,
      scenes: parsed.scenes,
      bgm: parsed.bgm,
      narration: parsed.narration,
    };
    if (template.category === category) return template;
  }
  const files2 = readdirSync(dir).filter((f) => f.endsWith(".yaml") || f.endsWith(".yml"));
  const firstFile = files2[0];
  if (!firstFile) throw new Error("シーンテンプレートファイルが見つかりません");
  const content = readFileSync(join(dir, firstFile), "utf-8");
  const parsed = YAML.parse(content) as { scene_template: SceneTemplate } & { scenes: SceneTemplate["scenes"]; bgm: SceneTemplate["bgm"]; narration: SceneTemplate["narration"] };
  return { ...parsed.scene_template, scenes: parsed.scenes, bgm: parsed.bgm, narration: parsed.narration };
}

export function loadCaptionTemplates(category: string): CaptionTemplate[] {
  const dir = join(CONFIG_DIR, "captions");
  const files = readdirSync(dir).filter((f) => f.endsWith(".yaml") || f.endsWith(".yml"));
  for (const file of files) {
    const content = readFileSync(join(dir, file), "utf-8");
    const parsed = YAML.parse(content) as { templates: CaptionTemplate[] };
    return parsed.templates;
  }
  return [];
}

export function selectProduct(products: Product[], usedIds: string[] = []): Product {
  const available = products.filter((p) => !usedIds.includes(p.id));
  const pool = available.length > 0 ? available : products;
  const item = pool[Math.floor(Math.random() * pool.length)];
  if (!item) throw new Error("商品が見つかりません");
  return item;
}

export function selectCaptionTemplate(
  templates: CaptionTemplate[],
  usedIds: string[] = [],
): CaptionTemplate {
  const available = templates.filter((t) => !usedIds.includes(t.id));
  const pool = available.length > 0 ? available : templates;
  const item = pool[Math.floor(Math.random() * pool.length)];
  if (!item) throw new Error("テンプレートが見つかりません");
  return item;
}
