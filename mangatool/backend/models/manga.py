"""
漫画関連モデル
"""
from sqlalchemy import Column, String, Integer, Text, DateTime, ForeignKey, Enum as SQLEnum, JSON
from sqlalchemy.dialects.postgresql import UUID
from sqlalchemy.orm import relationship
from datetime import datetime
import uuid
import enum

from core.database import Base


class MangaStatus(str, enum.Enum):
    """漫画ステータス"""
    DRAFT = "draft"
    GENERATING = "generating"
    COMPLETED = "completed"
    FAILED = "failed"


class CreationMethod(str, enum.Enum):
    """制作方法"""
    URL = "url"  # URL貼付（広告漫画）
    IMAGE = "image"  # キャラクター画像ベース
    TEXT = "text"  # テキストベース（原作）


class Manga(Base):
    """漫画テーブル"""
    __tablename__ = "mangas"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    user_id = Column(UUID(as_uuid=True), ForeignKey("users.id"), nullable=False)

    # 基本情報
    title = Column(String(255), nullable=False)
    description = Column(Text, nullable=True)
    # DB側のenum型は manga_status / creation_method (小文字ラベル) なので名前と値を明示する
    status = Column(
        SQLEnum(MangaStatus, name="manga_status", values_callable=lambda e: [m.value for m in e]),
        default=MangaStatus.DRAFT,
    )
    creation_method = Column(
        SQLEnum(CreationMethod, name="creation_method", values_callable=lambda e: [m.value for m in e]),
        nullable=False,
    )

    # 入力データ
    source_url = Column(String(2048), nullable=True)  # URL制作用
    source_images = Column(JSON, nullable=True)  # 画像制作用（画像URL配列）
    source_text = Column(Text, nullable=True)  # テキスト制作用

    # 台本データ（GPT-5.1生成）
    script_data = Column(JSON, nullable=True)  # 台本JSON
    storyboard_data = Column(JSON, nullable=True)  # 台割りJSON

    # メタデータ
    total_pages = Column(Integer, default=0)
    language = Column(String(10), default="ja")  # ja, en, zh, ko

    # サムネイル
    thumbnail_url = Column(String(2048), nullable=True)

    # タイムスタンプ
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    # リレーション
    user = relationship("User", back_populates="mangas")
    pages = relationship("MangaPage", back_populates="manga", cascade="all, delete-orphan", order_by="MangaPage.page_number")
    booth_listings = relationship("BOOTHListing", back_populates="manga")
    booth_sales = relationship("BOOTHSale", back_populates="manga")

    def __repr__(self):
        return f"<Manga {self.title}>"


class MangaPage(Base):
    """漫画ページテーブル"""
    __tablename__ = "manga_pages"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    manga_id = Column(UUID(as_uuid=True), ForeignKey("mangas.id"), nullable=False)

    # ページ情報
    page_number = Column(Integer, nullable=False)
    page_image_url = Column(String(2048), nullable=True)  # 完成ページ画像

    # ページ内容
    page_script = Column(Text, nullable=True)  # このページの台本
    layout_data = Column(JSON, nullable=True)  # レイアウト情報

    # タイムスタンプ
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    # リレーション
    manga = relationship("Manga", back_populates="pages")
    panels = relationship("MangaPanel", back_populates="page", cascade="all, delete-orphan", order_by="MangaPanel.panel_number")

    def __repr__(self):
        return f"<MangaPage {self.manga_id} - Page {self.page_number}>"


class MangaPanel(Base):
    """漫画コマテーブル"""
    __tablename__ = "manga_panels"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    page_id = Column(UUID(as_uuid=True), ForeignKey("manga_pages.id"), nullable=False)

    # コマ情報
    panel_number = Column(Integer, nullable=False)
    panel_image_url = Column(String(2048), nullable=True)  # コマ画像

    # コマ内容
    description = Column(Text, nullable=True)  # シーン説明
    dialogue = Column(JSON, nullable=True)  # セリフ配列 [{"character": "名前", "text": "セリフ"}]
    characters = Column(JSON, nullable=True)  # 登場キャラクター配列

    # 画像生成プロンプト
    image_prompt = Column(Text, nullable=True)  # Google Imagen用プロンプト

    # レイアウト
    position_x = Column(Integer, nullable=True)
    position_y = Column(Integer, nullable=True)
    width = Column(Integer, nullable=True)
    height = Column(Integer, nullable=True)

    # タイムスタンプ
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    # リレーション
    page = relationship("MangaPage", back_populates="panels")

    def __repr__(self):
        return f"<MangaPanel Page:{self.page_id} - Panel {self.panel_number}>"
