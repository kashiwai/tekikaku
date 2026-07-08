"""
アプリケーション設定
"""
from pydantic_settings import BaseSettings
from functools import lru_cache


class Settings(BaseSettings):
    # Database
    database_url: str = "postgresql+asyncpg://user:password@localhost:5432/mangatool"

    # Supabase
    supabase_url: str = ""
    supabase_anon_key: str = ""
    supabase_service_role_key: str = ""

    # JWT
    secret_key: str = "your-secret-key-here"
    algorithm: str = "HS256"
    access_token_expire_minutes: int = 30

    # OpenAI (未使用: 旧テキストLLM)
    openai_api_key: str = ""
    openai_model: str = "gpt-4o"

    # Google GenAI
    google_genai_api_key: str = ""
    # 画像生成: nanobanana Pro
    google_imagen_model: str = "gemini-3-pro-image"
    # テキストLLM: 台本・コマ割り・プロンプト生成
    gemini_text_model: str = "gemini-3.5-flash"

    # Google Cloud
    google_cloud_project: str = ""
    gcs_bucket_name: str = ""

    # Server
    host: str = "0.0.0.0"
    port: int = 8000
    debug: bool = True

    # BOOTH Browser Automation (Playwright)
    booth_email: str = ""
    booth_password: str = ""
    booth_headless: bool = True

    # CORS
    frontend_url: str = "http://localhost:3000"

    class Config:
        env_file = ".env"
        case_sensitive = False
        extra = "ignore"


@lru_cache()
def get_settings() -> Settings:
    return Settings()


settings = get_settings()
