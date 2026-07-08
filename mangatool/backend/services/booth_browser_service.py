"""
BOOTH ブラウザ自動化サービス（Playwright）
"""
import asyncio
import logging
from typing import Optional, Dict, Any
from playwright.async_api import async_playwright, Page, Browser, BrowserContext
from core.config import settings

logger = logging.getLogger(__name__)


class BOOTHBrowserService:
    """Playwright を使用した BOOTH Web UI 自動化"""

    def __init__(self):
        self.browser: Optional[Browser] = None
        self.context: Optional[BrowserContext] = None
        self.page: Optional[Page] = None

    async def initialize(self):
        """ブラウザを初期化"""
        try:
            self.playwright = await async_playwright().start()
            self.browser = await self.playwright.chromium.launch(headless=True)
            self.context = await self.browser.new_context()
            self.page = await self.context.new_page()
            logger.info("BOOTH Browser Service initialized")
        except Exception as e:
            logger.error(f"Failed to initialize browser: {e}")
            raise

    async def close(self):
        """ブラウザを閉じる"""
        if self.page:
            await self.page.close()
        if self.context:
            await self.context.close()
        if self.browser:
            await self.browser.close()
        if hasattr(self, 'playwright'):
            await self.playwright.stop()
        logger.info("BOOTH Browser Service closed")

    async def login(self, email: str, password: str) -> bool:
        """BOOTH にログイン"""
        try:
            await self.page.goto("https://booth.pm/login", wait_until="networkidle")

            # メールアドレス入力
            await self.page.fill('input[name="email"]', email)
            await self.page.fill('input[name="password"]', password)

            # ログインボタンをクリック
            await self.page.click('button[type="submit"]')

            # ダッシュボードへのリダイレクトを待つ
            await self.page.wait_for_url("**/dashboard", timeout=10000)

            logger.info(f"Successfully logged in as {email}")
            return True
        except Exception as e:
            logger.error(f"Login failed: {e}")
            return False

    async def create_product(
        self,
        title: str,
        description: str,
        price: int,
        pdf_url: str,
    ) -> Optional[Dict[str, Any]]:
        """
        BOOTH に商品を作成

        Args:
            title: 商品タイトル
            description: 商品説明
            price: 価格（JPY）
            pdf_url: PDF ファイル URL

        Returns:
            商品情報（item_id, booth_url を含む）
        """
        try:
            # 商品作成ページへ移動
            await self.page.goto("https://booth.pm/seller/items/new", wait_until="networkidle")

            # タイトル入力
            await self.page.fill('input[name="item[name]"]', title)

            # 説明入力
            await self.page.fill('textarea[name="item[description]"]', description)

            # 価格入力
            await self.page.fill('input[name="item[price]"]', str(price))

            # ファイルタイプを「デジタル製品」に設定
            await self.page.click('select[name="item[type]"]')
            await self.page.click('text=デジタル製品')

            # PDF をアップロード
            await self._upload_file(pdf_url)

            # 発行者情報を設定
            await self.page.fill('input[name="item[artist_name]"]', "comicCockpit 出版")

            # 保存ボタンをクリック
            await self.page.click('button:has-text("保存")')

            # 商品ページへのリダイレクトを待つ
            await asyncio.sleep(3)

            # 現在のURLから item_id を取得
            url = self.page.url
            item_id = url.split("/")[-1] if url else None

            logger.info(f"Successfully created product: {item_id}")
            return {
                "success": True,
                "item_id": item_id,
                "booth_url": url,
            }
        except Exception as e:
            logger.error(f"Failed to create product: {e}")
            return {"success": False, "error": str(e)}

    async def _upload_file(self, file_url: str) -> bool:
        """
        ファイルをアップロード

        Args:
            file_url: ファイル URL

        Returns:
            成功したかどうか
        """
        try:
            # ファイルアップロード入力を取得
            file_input = await self.page.query_selector('input[type="file"]')

            if not file_input:
                logger.error("File input not found")
                return False

            # URL からファイルをダウンロードしてアップロード
            import tempfile
            import requests

            response = requests.get(file_url, timeout=30)

            with tempfile.NamedTemporaryFile(suffix=".pdf", delete=False) as tmp:
                tmp.write(response.content)
                tmp_path = tmp.name

            # ファイルをアップロード
            await file_input.set_input_files(tmp_path)

            logger.info(f"Successfully uploaded file from {file_url}")
            return True
        except Exception as e:
            logger.error(f"File upload failed: {e}")
            return False

    async def get_sales(self) -> list:
        """
        売上データを取得（スクレイピング）

        Returns:
            売上データのリスト
        """
        try:
            await self.page.goto("https://booth.pm/seller/sales", wait_until="networkidle")

            # 売上テーブルから行を取得
            sales_rows = await self.page.query_selector_all("table tbody tr")

            sales = []
            for row in sales_rows:
                # セルを取得
                cells = await row.query_selector_all("td")

                if len(cells) >= 4:
                    order_id = await cells[0].text_content()
                    amount = await cells[1].text_content()
                    date = await cells[2].text_content()

                    sales.append({
                        "order_id": order_id.strip(),
                        "amount": int(amount.replace("¥", "").replace(",", "")),
                        "date": date.strip(),
                    })

            logger.info(f"Fetched {len(sales)} sales")
            return sales
        except Exception as e:
            logger.error(f"Failed to get sales: {e}")
            return []

    async def get_product_status(self, item_id: str) -> Optional[Dict[str, Any]]:
        """
        商品ステータスを取得

        Args:
            item_id: BOOTH 商品 ID

        Returns:
            商品情報
        """
        try:
            await self.page.goto(f"https://booth.pm/seller/items/{item_id}", wait_until="networkidle")

            # 商品情報を取得
            title = await self.page.text_content('h1.product-title')
            price = await self.page.text_content('.product-price')
            sales_count = await self.page.text_content('.sales-count')

            return {
                "item_id": item_id,
                "title": title.strip() if title else None,
                "price": price.strip() if price else None,
                "sales_count": sales_count.strip() if sales_count else None,
            }
        except Exception as e:
            logger.error(f"Failed to get product status: {e}")
            return None


# グローバルインスタンス
booth_browser_service = BOOTHBrowserService()
