"""
漫画を PDF に合成するサービス
"""
import io
from typing import List, Optional
from PIL import Image
from reportlab.lib.pagesizes import A4, B5
from reportlab.pdfgen import canvas
from reportlab.lib.utils import ImageReader


class PDFService:
    """漫画ページを PDF に合成するサービス"""

    def __init__(self):
        self.page_width, self.page_height = B5  # B5 サイズ（182mm × 257mm）

    async def create_pdf_from_images(
        self,
        image_urls: List[str],
        title: Optional[str] = None,
        author: Optional[str] = None,
    ) -> bytes:
        """
        複数の画像 URL から PDF を生成

        Args:
            image_urls: 画像の URL リスト（順序がページ順）
            title: 漫画のタイトル
            author: 著者名

        Returns:
            PDF ファイルのバイナリデータ
        """
        pdf_buffer = io.BytesIO()

        # Canvas を作成
        c = canvas.Canvas(pdf_buffer, pagesize=(self.page_width, self.page_height))

        # タイトルページを追加（オプション）
        if title:
            self._add_title_page(c, title, author)
            c.showPage()

        # 各ページを追加
        for image_url in image_urls:
            try:
                # 画像を取得（ローカルパスまたは URL）
                img = self._load_image(image_url)
                if img:
                    self._add_image_page(c, img)
                    c.showPage()
            except Exception as e:
                print(f"Warning: Failed to add image {image_url}: {e}")
                continue

        # PDF を保存
        c.save()

        pdf_buffer.seek(0)
        return pdf_buffer.getvalue()

    def _load_image(self, image_path: str) -> Optional[Image.Image]:
        """
        ローカルパスまたは URL から画像を読み込む

        Args:
            image_path: 画像ファイルパスまたは URL

        Returns:
            PIL Image オブジェクト、または None
        """
        try:
            if image_path.startswith('http'):
                # URL の場合は Supabase Storage から取得
                import urllib.request
                with urllib.request.urlopen(image_path) as response:
                    img = Image.open(io.BytesIO(response.read()))
            else:
                # ローカルパス
                img = Image.open(image_path)

            return img
        except Exception as e:
            print(f"Error loading image {image_path}: {e}")
            return None

    def _add_image_page(self, c: canvas.Canvas, img: Image.Image) -> None:
        """
        画像をキャンバスに追加

        Args:
            c: ReportLab Canvas オブジェクト
            img: PIL Image
        """
        # 画像を B5 サイズにフィット
        img_width, img_height = img.size

        # アスペクト比を保持してスケーリング
        max_width = self.page_width - 20  # マージン
        max_height = self.page_height - 20

        scale = min(max_width / img_width, max_height / img_height)
        new_width = img_width * scale
        new_height = img_height * scale

        # 中央配置
        x = (self.page_width - new_width) / 2
        y = (self.page_height - new_height) / 2

        # 一時的に PNG に変換（ReportLab は JPEG/PNG のみサポート）
        if img.format != 'JPEG' and img.format != 'PNG':
            img = img.convert('RGB')

        # バッファに保存
        img_buffer = io.BytesIO()
        img.save(img_buffer, format='PNG')
        img_buffer.seek(0)

        # キャンバスに描画
        img_reader = ImageReader(img_buffer)
        c.drawImage(img_reader, x, y, width=new_width, height=new_height)

    def _add_title_page(self, c: canvas.Canvas, title: str, author: Optional[str]) -> None:
        """
        タイトルページを追加

        Args:
            c: ReportLab Canvas オブジェクト
            title: 漫画タイトル
            author: 著者名
        """
        from reportlab.lib import colors
        from reportlab.pdfbase import pdfmetrics
        from reportlab.pdfbase.ttfonts import TTFont

        # 背景
        c.setFillColor(colors.white)
        c.rect(0, 0, self.page_width, self.page_height, fill=1)

        # タイトル（中央）
        c.setFont("Helvetica-Bold", 28)
        title_y = self.page_height * 0.6
        c.drawCentredString(self.page_width / 2, title_y, title[:40])

        # 著者名
        if author:
            c.setFont("Helvetica", 14)
            author_y = title_y - 40
            c.drawCentredString(self.page_width / 2, author_y, f"著者：{author}")

        # 出版社
        c.setFont("Helvetica", 12)
        publisher_y = self.page_height * 0.2
        c.drawCentredString(self.page_width / 2, publisher_y, "comicCockpit 出版")


pdf_service = PDFService()
