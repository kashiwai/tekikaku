"""
BOOTH 連携関連のモデル
"""
from uuid import uuid4
from datetime import datetime
from sqlalchemy import Column, String, Integer, Float, DateTime, ForeignKey, Enum, Text
from sqlalchemy.dialects.postgresql import UUID
from sqlalchemy.orm import relationship
import enum

from db.base import Base
from models.user import User
from models.manga import Manga


class BOOTHListingStatus(str, enum.Enum):
    """BOOTH 出品ステータス"""
    DRAFT = "draft"  # 下書き
    PENDING = "pending"  # 出品待機中
    PUBLISHED = "published"  # 出品済み
    ARCHIVED = "archived"  # アーカイブ


class BOOTHListing(Base):
    """BOOTH への出品情報"""
    __tablename__ = "booth_listings"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid4)
    manga_id = Column(UUID(as_uuid=True), ForeignKey("mangas.id"), nullable=False)
    user_id = Column(UUID(as_uuid=True), ForeignKey("users.id"), nullable=False)

    # 出品情報
    booth_item_id = Column(String, unique=True, nullable=True)  # BOOTH の item_id
    booth_url = Column(String, nullable=True)  # BOOTH でのURL
    title = Column(String, nullable=False)  # BOOTH での商品タイトル
    description = Column(Text, nullable=True)  # 商品説明
    store_price = Column(Integer, nullable=False)  # お客様が設定した販売価格（JPY）

    # ステータス
    status = Column(Enum(BOOTHListingStatus), default=BOOTHListingStatus.DRAFT)

    # BOOTH API レスポンス
    booth_response = Column(String, nullable=True)  # 最後のAPIレスポンス（JSON）

    # タイムスタンプ
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    published_at = Column(DateTime, nullable=True)

    # リレーション
    manga = relationship("Manga", back_populates="booth_listings")
    user = relationship("User", back_populates="booth_listings")
    sales = relationship("BOOTHSale", back_populates="listing")


class BOOTHSale(Base):
    """BOOTH での売上記録"""
    __tablename__ = "booth_sales"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid4)
    listing_id = Column(UUID(as_uuid=True), ForeignKey("booth_listings.id"), nullable=False)
    manga_id = Column(UUID(as_uuid=True), ForeignKey("mangas.id"), nullable=False)
    user_id = Column(UUID(as_uuid=True), ForeignKey("users.id"), nullable=False)

    # 売上情報
    booth_order_id = Column(String, unique=True, nullable=False)  # BOOTH の order_id
    amount = Column(Integer, nullable=False)  # 売上金額（JPY）

    # 顧客情報
    buyer_name = Column(String, nullable=True)
    buyer_email = Column(String, nullable=True)

    # タイムスタンプ
    sold_at = Column(DateTime, nullable=False)  # 販売日時
    created_at = Column(DateTime, default=datetime.utcnow)

    # リレーション
    listing = relationship("BOOTHListing", back_populates="sales")
    manga = relationship("Manga", back_populates="booth_sales")
    user = relationship("User", back_populates="booth_sales")
    commissions = relationship("CommissionPayment", back_populates="sale")


class CommissionPayment(Base):
    """配分・支払い管理"""
    __tablename__ = "commission_payments"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid4)
    sale_id = Column(UUID(as_uuid=True), ForeignKey("booth_sales.id"), nullable=False)
    manga_id = Column(UUID(as_uuid=True), ForeignKey("mangas.id"), nullable=False)
    seller_id = Column(UUID(as_uuid=True), ForeignKey("users.id"), nullable=False)  # お客様
    store_id = Column(UUID(as_uuid=True), ForeignKey("users.id"), nullable=False)  # mangatool 事業者

    # 配分金額
    gross_amount = Column(Integer, nullable=False)  # 売上（税抜き）
    seller_commission = Column(Integer, nullable=False)  # お客様の配分（60%）
    store_commission = Column(Integer, nullable=False)  # 事業者の配分（40%）

    # 支払い状況
    seller_paid = Column(String, default="pending")  # pending / completed / failed
    store_paid = Column(String, default="pending")

    seller_paid_at = Column(DateTime, nullable=True)
    store_paid_at = Column(DateTime, nullable=True)

    # タイムスタンプ
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    # リレーション
    sale = relationship("BOOTHSale", back_populates="commissions")
    manga = relationship("Manga")
    seller = relationship("User", foreign_keys=[seller_id])
    store = relationship("User", foreign_keys=[store_id])
