import { parse } from "node-html-parser";
import { execSync } from "child_process";
import { mkdirSync, existsSync, readdirSync, statSync, unlinkSync } from "fs";
import { join, extname } from "path";

const UA = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36";

// URLから商品画像リストを取得
export async function scrapeProductImages(affiliateUrl: string): Promise<string[]> {
  const host = getHost(affiliateUrl);

  // 楽天: 商品ID → 楽天APIで画像取得
  if (host.includes("rakuten")) {
    const images = await fetchRakutenImages(affiliateUrl);
    if (images.length > 0) return images;
  }

  // Amazon: ASINから画像URL構築（ASIN解析）
  if (host.includes("amazon")) {
    const images = fetchAmazonImages(affiliateUrl);
    if (images.length > 0) return images;
  }

  // 汎用: og:image + img タグ解析
  return fetchGenericImages(affiliateUrl);
}

// シーンフォルダすべてに商品画像を配布（scene1〜7）
export async function setupMaterialsFromUrl(
  affiliateUrl: string,
  baseMaterialsDir: string,
  sceneCount = 7,
): Promise<void> {
  console.log("[スクレイパー] 商品画像を取得中...");

  const images = await scrapeProductImages(affiliateUrl);
  if (images.length === 0) {
    console.warn("[スクレイパー] 画像が取得できませんでした。materials/ に手動で画像を配置してください。");
    return;
  }

  for (let sceneId = 1; sceneId <= sceneCount; sceneId++) {
    const sceneDir = join(baseMaterialsDir, `scene${sceneId}`);
    mkdirSync(sceneDir, { recursive: true });

    const existing = readdirSync(sceneDir).filter(f =>
      [".jpg", ".jpeg", ".png", ".webp"].includes(extname(f).toLowerCase())
    );
    if (existing.length > 0) continue;

    const imgUrl = images[(sceneId - 1) % images.length]!;
    const ext = getImageExt(imgUrl);
    const destPath = join(sceneDir, `1_product${ext}`);

    try {
      downloadFile(imgUrl, destPath);
      console.log(`[スクレイパー] scene${sceneId} に画像を配置`);
    } catch (e) {
      console.warn(`[スクレイパー] scene${sceneId} の画像配置失敗:`, e);
    }
  }

  console.log("[スクレイパー] 素材のセットアップ完了");
}

// 楽天Ichiba APIで画像取得（RAKUTEN_APP_ID が必要）
async function fetchRakutenImages(url: string): Promise<string[]> {
  const appId = process.env.RAKUTEN_APP_ID;
  if (!appId) return [];

  // URLから商品コードを取得 (例: /item.rakuten.co.jp/shop/ITEMCODE/)
  const match = url.match(/rakuten\.co\.jp\/([^/]+)\/([^/?#]+)/);
  if (!match) return [];

  const shopCode = match[1]!;
  const itemCode = match[2]!;

  try {
    const apiUrl = `https://app.rakuten.co.jp/services/api/IchibaItem/Search/20170706?applicationId=${appId}&shopCode=${shopCode}&itemCode=${itemCode}&hits=1&format=json`;
    const res = fetchHtml(apiUrl);
    if (!res) return [];
    const data = JSON.parse(res) as { Items?: Array<{ Item: { mediumImageUrls?: Array<{ imageUrl: string }> } }> };
    const item = data.Items?.[0]?.Item;
    if (!item) return [];
    return (item.mediumImageUrls ?? [])
      .map(i => i.imageUrl.replace("?_ex=128x128", ""))
      .filter(Boolean);
  } catch {
    return [];
  }
}

// AmazonのASINから画像URL推測
function fetchAmazonImages(url: string): string[] {
  const asinMatch = url.match(/\/dp\/([A-Z0-9]{10})|\/([A-Z0-9]{10})(?:\/|\?|$)/);
  if (!asinMatch) return [];
  const asin = asinMatch[1] ?? asinMatch[2]!;

  // Amazon商品画像のCDNパターン（複数サイズ）
  return [
    `https://m.media-amazon.com/images/P/${asin}.09.LZZZZZZZ.jpg`,
    `https://m.media-amazon.com/images/P/${asin}.09._SL1500_.jpg`,
  ];
}

// 汎用サイト: og:image + img タグ
function fetchGenericImages(url: string): string[] {
  const html = fetchHtml(url);
  if (!html) return [];

  const root = parse(html);
  const images: string[] = [];
  const seen = new Set<string>();

  const add = (src: string) => {
    if (!src || seen.has(src)) return;
    try {
      const abs = src.startsWith("http") ? src : new URL(src, url).href;
      if (abs.match(/logo|icon|banner|sprite|1x1|svg|tracking/i)) return;
      if (!abs.match(/\.(jpe?g|png|webp)(\?|$)/i)) return;
      seen.add(abs);
      images.push(abs);
    } catch { /* ignore */ }
  };

  // og:image を最優先
  const og = root.querySelector('meta[property="og:image"]');
  if (og) add(og.getAttribute("content") ?? "");

  // product 関連 img
  root.querySelectorAll(
    'img.product-image, img.item-image, img[class*="product"], img[id*="main"]'
  ).forEach(img => add(img.getAttribute("src") ?? img.getAttribute("data-src") ?? ""));

  // 全 img フォールバック
  root.querySelectorAll("img").forEach(img => {
    add(img.getAttribute("src") ?? img.getAttribute("data-src") ?? img.getAttribute("data-lazy-src") ?? "");
  });

  return images;
}

function fetchHtml(url: string): string | null {
  try {
    return execSync(
      `curl -sL --max-time 15 --max-redirs 5 -A "${UA}" -H "Accept: text/html,application/json" -H "Accept-Language: ja,en;q=0.9" "${url}"`,
      { encoding: "utf-8", timeout: 20000, maxBuffer: 10 * 1024 * 1024 }
    );
  } catch {
    return null;
  }
}

function downloadFile(url: string, destPath: string): void {
  execSync(`curl -sL --max-time 30 -A "${UA}" -o "${destPath}" "${url}"`, { timeout: 35000 });
  const stat = statSync(destPath);
  if (stat.size < 1000) {
    unlinkSync(destPath);
    throw new Error(`ダウンロードファイルが小さすぎます (${stat.size} bytes)`);
  }
}

function getHost(url: string): string {
  try { return new URL(url).hostname; } catch { return ""; }
}

function getImageExt(url: string): string {
  const match = url.match(/\.(jpe?g|png|webp)/i);
  return match ? match[0]!.toLowerCase().replace("jpeg", "jpg") : ".jpg";
}
