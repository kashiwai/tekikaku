"""
ユーザーモデル
"""
from sqlalchemy import Column, String, Boolean, DateTime, Enum as SQLEnum
from sqlalchemy.dialects.postgresql import UUID
from sqlalchemy.orm import relationship
from datetime import datetime
import uuid
import enum

from core.database import Base


class UserRole(str, enum.Enum):
    """ユーザーロール"""
    FREE = "free"
    CREATOR = "creator"  # 同人・クリエイタープラン
    BUSINESS = "business"  # 法人広告プラン
    TEXT_CREATOR = "text_creator"  # テキスト原作プラン
    ADMIN = "admin"


class User(Base):
    """ユーザーテーブル"""
    __tablename__ = "users"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    email = Column(String(255), unique=True, nullable=False, index=True)
    hashed_password = Column(String(255), nullable=False)
    name = Column(String(100), nullable=True)
    role = Column(SQLEnum(UserRole), default=UserRole.FREE, nullable=False)
    is_active = Column(Boolean, default=True)
    is_verified = Column(Boolean, default=True)  # 開発時はTrue、本番でメール確認実装後にFalseに

    # Supabase Auth連携用
    supabase_uid = Column(String(255), unique=True, nullable=True)

    # タイムスタンプ
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    # リレーション
    mangas = relationship("Manga", back_populates="user", cascade="all, delete-orphan")
    subscription = relationship("Subscription", back_populates="user", uselist=False)
    booth_listings = relationship("BOOTHListing", back_populates="user")
    booth_sales = relationship("BOOTHSale", back_populates="user")

    def __repr__(self):
        return f"<User {self.email}>"
