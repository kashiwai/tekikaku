"""
漫画生成API
"""
from fastapi import APIRouter, Depends, HTTPException, status, BackgroundTasks
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import select
from sqlalchemy.orm import selectinload
from typing import List
from uuid import UUID
import asyncio

from core.database import get_db
from models.user import User
from models.manga import Manga, MangaPage, MangaPanel, MangaStatus, CreationMethod
from schemas.manga import (
    MangaCreate,
    MangaResponse,
    MangaUpdate,
    MangaGenerateRequest,
    MangaListResponse,
    MangaPageResponse,
    ScriptEditRequest,
    PageEditRequest,
    PanelEditRequest,
    PanelRegenerateRequest
)
from services.gpt_service import gpt_service
from services.imagen_service import imagen_service
from services.manga_composer import manga_composer
from .auth import get_current_user

router = APIRouter()


def _manga_query_with_pages():
    """pages→panels をあらかじめロードするクエリ（asyncの遅延ロード回避）"""
    return select(Manga).options(
        selectinload(Manga.pages).selectinload(MangaPage.panels)
    )


@router.post("/generate", response_model=MangaResponse)
async def generate_manga(
    request: MangaGenerateRequest,
    background_tasks: BackgroundTasks,
    current_user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db)
):
    """
    漫画を生成

    3つの制作方法:
    - URL: LPO URLから広告漫画を生成
    - IMAGE: キャラクター画像からストーリー作成
    - TEXT: テキストから漫画を生成
    """
    # 入力検証
    if request.creation_method == CreationMethod.URL and not request.source_url:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="URL制作にはsource_urlが必要です"
        )
    if request.creation_method == CreationMethod.IMAGE and not request.source_images:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="画像制作にはsource_imagesが必要です"
        )
    if request.creation_method == CreationMethod.TEXT and not request.source_text:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="テキスト制作にはsource_textが必要です"
        )

    # ページ数の検証（1～250ページ）
    if request.num_pages < 1 or request.num_pages > 250:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="ページ数は 1～250 の間で指定してください"
        )

    # 漫画レコード作成
    manga = Manga(
        user_id=current_user.id,
        title=request.title,
        description=request.description,
        status=MangaStatus.GENERATING,
        creation_method=request.creation_method,
        source_url=request.source_url,
        source_images=request.source_images,
        source_text=request.source_text,
        language=request.language
    )
    db.add(manga)
    await db.commit()
    await db.refresh(manga)

    # バックグラウンドで生成処理を実行
    background_tasks.add_task(
        generate_manga_task,
        manga_id=str(manga.id),
        request=request
    )

    result = await db.execute(_manga_query_with_pages().where(Manga.id == manga.id))
    return result.scalar_one()


# ============================================================
# 段階別生成フロー: 台本 → コマ割り → キャラクター → 本編
# 各段階の成果物は script_data / storyboard_data / script_data.characters[].sheet_image_url に保存し、
# フロントは GET /manga/{id} をポーリングして進行を検知する。
# エラーは各JSONフィールドに {"error": "..."} を書き込んで通知する。
# ============================================================


def _build_story_input(manga: Manga) -> str:
    """漫画レコードからストーリー入力テキストを組み立てる"""
    if manga.creation_method == CreationMethod.TEXT:
        return manga.source_text or ""
    if manga.creation_method == CreationMethod.URL:
        return manga.source_url or ""
    return manga.description or "画像のキャラクターを使ったストーリー"


# --- 段階タスクのDBヘルパー (LLM/画像処理中はDB接続を握らない) ---
# Supabaseプーラーは長時間アイドルの接続を切るため、
# 「短時間の読み取り → 接続解放 → 重い処理 → 短時間の書き込み」で分離する。

async def _load_manga(manga_id: str):
    """漫画レコードを取得して必要フィールドをdictで返す (接続は即解放)"""
    from core.database import async_session
    async with async_session() as db:
        m = (await db.execute(select(Manga).where(Manga.id == UUID(manga_id)))).scalar_one_or_none()
        if not m:
            return None
        return {
            "id": m.id,
            "creation_method": m.creation_method,
            "source_text": m.source_text,
            "source_url": m.source_url,
            "description": m.description,
            "language": m.language,
            "script_data": m.script_data,
            "storyboard_data": m.storyboard_data,
        }


async def _save_manga_field(manga_id: str, **fields):
    """漫画レコードの指定フィールドを更新 (短時間の書き込み)"""
    from core.database import async_session
    from sqlalchemy.orm.attributes import flag_modified
    async with async_session() as db:
        m = (await db.execute(select(Manga).where(Manga.id == UUID(manga_id)))).scalar_one_or_none()
        if not m:
            return
        for k, v in fields.items():
            setattr(m, k, v)
            if k in ("script_data", "storyboard_data"):
                flag_modified(m, k)
        await db.commit()


async def _stage_script_task(
    manga_id: str,
    num_pages: int,
    style: str,
    language: str,
    make_cover: bool = True,
    cover_colored: bool = True,
    parent_manga_id: str = None,
):
    """段階1: 台本生成タスク"""
    data = await _load_manga(manga_id)
    if not data:
        return
    try:
        if data["creation_method"] == CreationMethod.TEXT:
            story_input = data["source_text"] or ""
        elif data["creation_method"] == CreationMethod.URL:
            story_input = data["source_url"] or ""
        else:
            story_input = data["description"] or "画像のキャラクターを使ったストーリー"

        if data["creation_method"] == CreationMethod.URL and data["source_url"]:
            content = await gpt_service.extract_content_from_url(data["source_url"])
            story_input = content.get("suggested_story_outline") or str(content)

        # 続き作成: 前作の台本を文脈として渡す
        prev_characters = None
        if parent_manga_id:
            prev = await _load_manga(parent_manga_id)
            if prev and prev.get("script_data"):
                psd = prev["script_data"]
                prev_characters = psd.get("characters")
                prev_summary = psd.get("synopsis") or psd.get("logline") or ""
                char_names = "、".join(c.get("name", "") for c in (prev_characters or []))
                story_input = (
                    f"以下は前作『{psd.get('title', '')}』の続編です。\n"
                    f"前作のあらすじ: {prev_summary}\n"
                    f"登場人物（同じキャラクターを引き続き使うこと）: {char_names}\n"
                    f"続きの展開の希望: {story_input}\n"
                    f"前作の続きとして自然につながる新しいエピソードを作ってください。"
                )

        script = await gpt_service.generate_script(
            story_input=story_input,
            num_pages=num_pages,
            style=style,
            language=data["language"] or language,
        )
        # 続き作成時は前作のキャラクター定義を引き継ぐ (見た目の一貫性)
        if prev_characters:
            existing = {c.get("name") for c in script.get("characters", [])}
            for pc in prev_characters:
                pc = {k: v for k, v in pc.items() if k not in ("sheet_image_url", "sheet_error")}
                if pc.get("name") not in existing:
                    script.setdefault("characters", []).append(pc)
        script["_meta"] = {
            "num_pages": num_pages,
            "style": style,
            "make_cover": make_cover,
            "cover_colored": cover_colored,
            "parent_manga_id": parent_manga_id,
        }
        await _save_manga_field(manga_id, script_data=script)
    except Exception as e:
        print(f"Error generating script: {e}")
        await _save_manga_field(manga_id, script_data={"error": str(e)})


async def _stage_storyboard_task(manga_id: str, style: str):
    """段階2: コマ割り生成タスク"""
    data = await _load_manga(manga_id)
    if not data or not data["script_data"]:
        return
    try:
        storyboard = await gpt_service.generate_storyboard(
            script_data=data["script_data"],
            style=style,
        )
        await _save_manga_field(manga_id, storyboard_data=storyboard)
    except Exception as e:
        print(f"Error generating storyboard: {e}")
        await _save_manga_field(manga_id, storyboard_data={"error": str(e)})


async def _stage_characters_task(manga_id: str, style: str, only_name: str = None):
    """段階3: キャラクターシート生成タスク (1体ずつ生成→即保存)"""
    from core.database import async_session
    from services.storage_service import storage_service
    from sqlalchemy.orm.attributes import flag_modified

    data = await _load_manga(manga_id)
    if not data or not data["script_data"]:
        return
    characters = data["script_data"].get("characters", [])

    for ch in characters:
        name = ch.get("name", "")
        if only_name and name != only_name:
            continue
        if not only_name and ch.get("sheet_image_url"):
            continue

        # 画像生成 (DB接続は握らない)
        sheet_url, sheet_err = None, None
        try:
            img = await imagen_service.generate_character_sheet(
                name=name,
                description=ch.get("description", ""),
                style=style,
            )
            sheet_url = await storage_service.upload_image(img, prefix="characters")
        except Exception as e:
            print(f"Error generating character sheet for {name}: {e}")
            sheet_err = str(e)

        # 1体分だけ短時間で書き込む
        async with async_session() as db:
            m = (await db.execute(select(Manga).where(Manga.id == UUID(manga_id)))).scalar_one_or_none()
            if not m or not m.script_data:
                continue
            for c in m.script_data.get("characters", []):
                if c.get("name") == name:
                    if sheet_url:
                        c["sheet_image_url"] = sheet_url
                        c.pop("sheet_error", None)
                    else:
                        c["sheet_error"] = sheet_err
            flag_modified(m, "script_data")
            await db.commit()


async def _stage_pages_task(manga_id: str, style: str):
    """段階4: 本編 (コマ画像生成 + ページ合成) タスク"""
    from core.database import async_session
    from services.storage_service import storage_service
    import httpx

    data = await _load_manga(manga_id)
    if not data or not data["script_data"] or not data["storyboard_data"]:
        return

    try:
        # キャラクターシートをダウンロード (DB接続なし)
        character_sheets: dict[str, bytes] = {}
        async with httpx.AsyncClient(timeout=60) as client:
            for ch in data["script_data"].get("characters", []):
                url = ch.get("sheet_image_url")
                if url:
                    resp = await client.get(url)
                    if resp.status_code == 200:
                        character_sheets[ch.get("name", "")] = resp.content

        # コマ画像生成 + ページ合成 (最も時間がかかる。DB接続なし)
        pages = await manga_composer.generate_pages_staged(
            script_data=data["script_data"],
            storyboard_data=data["storyboard_data"],
            character_sheets=character_sheets,
            style=style,
        )

        # 表紙 (page_number=0) を生成 (_metaのオプションに従う)
        meta = (data["script_data"] or {}).get("_meta", {})
        if meta.get("make_cover", False):
            try:
                title = (data["script_data"] or {}).get("title") or "無題"
                cover_bytes = await manga_composer.generate_cover(
                    title=title,
                    script_data=data["script_data"],
                    character_sheets=character_sheets,
                    colored=meta.get("cover_colored", True),
                    style=style,
                )
                pages = [{"page_number": 0, "image_data": cover_bytes, "panels": []}] + pages
            except Exception as cover_err:
                print(f"Error generating cover: {cover_err}")

        # ページ画像をアップロード (DB接続なし)
        uploaded = []
        thumbnail_url = None
        for page_data in pages:
            page_image_url = None
            if page_data.get("image_data"):
                try:
                    page_image_url = await storage_service.upload_image(
                        page_data["image_data"], prefix="pages"
                    )
                except Exception as upload_err:
                    print(f"Error uploading page image: {upload_err}")
            if page_image_url and not thumbnail_url:
                thumbnail_url = page_image_url
            uploaded.append((page_data, page_image_url))

        # まとめてDBに書き込む (短時間)
        async with async_session() as db:
            m = (await db.execute(select(Manga).where(Manga.id == UUID(manga_id)))).scalar_one_or_none()
            if not m:
                return
            old_pages = (await db.execute(
                select(MangaPage).where(MangaPage.manga_id == m.id)
            )).scalars().all()
            for p in old_pages:
                await db.delete(p)
            await db.flush()

            for page_data, page_image_url in uploaded:
                page = MangaPage(
                    manga_id=m.id,
                    page_number=page_data.get("page_number", 1),
                    page_image_url=page_image_url,
                    page_script=str(page_data.get("panels", [])),
                    layout_data={"panels": page_data.get("panels", [])},
                )
                db.add(page)
                await db.flush()
                for panel_data in page_data.get("panels", []):
                    pos = panel_data.get("position") or {}
                    db.add(MangaPanel(
                        page_id=page.id,
                        panel_number=panel_data.get("panel_number", 1),
                        description=panel_data.get("description") or panel_data.get("composition"),
                        dialogue=panel_data.get("dialogues") or panel_data.get("dialogue"),
                        characters=panel_data.get("characters"),
                        image_prompt=panel_data.get("image_prompt") or panel_data.get("prompt"),
                        position_x=pos.get("column", 0),
                        position_y=pos.get("row", 0),
                        width=pos.get("span_columns", 1),
                        height=pos.get("span_rows", 1),
                    ))

            m.total_pages = len(uploaded)
            m.thumbnail_url = thumbnail_url
            m.status = MangaStatus.COMPLETED
            await db.commit()
    except Exception as e:
        print(f"Error generating pages: {e}")
        await _save_manga_field(manga_id, status=MangaStatus.FAILED)


@router.post("/staged", response_model=MangaResponse)
async def create_staged_manga(
    request: MangaGenerateRequest,
    background_tasks: BackgroundTasks,
    current_user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db)
):
    """段階別生成フローの開始: レコード作成 + 台本生成を起動"""
    if request.creation_method == CreationMethod.TEXT and not request.source_text:
        raise HTTPException(status_code=400, detail="テキスト制作にはsource_textが必要です")
    if request.creation_method == CreationMethod.URL and not request.source_url:
        raise HTTPException(status_code=400, detail="URL制作にはsource_urlが必要です")

    manga = Manga(
        user_id=current_user.id,
        title=request.title,
        description=request.description,
        status=MangaStatus.DRAFT,
        creation_method=request.creation_method,
        source_url=request.source_url,
        source_images=request.source_images,
        source_text=request.source_text,
        language=request.language,
    )
    db.add(manga)
    await db.commit()
    await db.refresh(manga)

    background_tasks.add_task(
        _stage_script_task,
        manga_id=str(manga.id),
        num_pages=request.num_pages,
        style=request.style,
        language=request.language,
        make_cover=request.make_cover,
        cover_colored=request.cover_colored,
        parent_manga_id=request.parent_manga_id,
    )

    result = await db.execute(_manga_query_with_pages().where(Manga.id == manga.id))
    return result.scalar_one()


@router.post("/{manga_id}/continue", response_model=MangaResponse)
async def continue_manga(
    manga_id: UUID,
    body: dict = None,
    background_tasks: BackgroundTasks = None,
    current_user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db),
):
    """
    前作の続き (続編) を新規マンガとして作成する。
    前作のキャラクターとあらすじを引き継ぎ、続きのストーリーを生成する。
    body (任意): {"title": "...", "num_pages": 8, "direction": "続きの希望", "make_cover": true, "cover_colored": true}
    """
    body = body or {}
    parent = await _get_own_manga(manga_id, current_user, db)
    if not parent.script_data or parent.script_data.get("error"):
        raise HTTPException(status_code=400, detail="前作の台本が無いため続きを作成できません")

    pmeta = (parent.script_data or {}).get("_meta", {})
    new_title = body.get("title") or f"{parent.title}（続き）"
    num_pages = int(body.get("num_pages") or pmeta.get("num_pages", 8))

    # ページ数の検証（1～250ページ）
    if num_pages < 1 or num_pages > 250:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="ページ数は 1～250 の間で指定してください"
        )

    style = body.get("style") or pmeta.get("style", "manga")
    make_cover = body.get("make_cover", pmeta.get("make_cover", True))
    cover_colored = body.get("cover_colored", pmeta.get("cover_colored", True))
    direction = body.get("direction") or "前作の余韻を活かした自然な続き"

    manga = Manga(
        user_id=current_user.id,
        title=new_title,
        description=parent.description,
        status=MangaStatus.DRAFT,
        creation_method=CreationMethod.TEXT,
        source_text=direction,
        language=parent.language or "ja",
    )
    db.add(manga)
    await db.commit()
    await db.refresh(manga)

    background_tasks.add_task(
        _stage_script_task,
        manga_id=str(manga.id),
        num_pages=num_pages,
        style=style,
        language=parent.language or "ja",
        make_cover=make_cover,
        cover_colored=cover_colored,
        parent_manga_id=str(manga_id),
    )

    result = await db.execute(_manga_query_with_pages().where(Manga.id == manga.id))
    return result.scalar_one()


async def _get_own_manga(manga_id: UUID, current_user: User, db: AsyncSession) -> Manga:
    result = await db.execute(
        select(Manga).where(Manga.id == manga_id, Manga.user_id == current_user.id)
    )
    manga = result.scalar_one_or_none()
    if not manga:
        raise HTTPException(status_code=404, detail="漫画が見つかりません")
    return manga


@router.post("/{manga_id}/script/generate")
async def regenerate_script_stage(
    manga_id: UUID,
    background_tasks: BackgroundTasks,
    num_pages: int = 8,
    style: str = "manga",
    current_user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db)
):
    """台本を(再)生成"""
    manga = await _get_own_manga(manga_id, current_user, db)
    meta = (manga.script_data or {}).get("_meta", {})
    manga.script_data = None
    manga.storyboard_data = None
    await db.commit()
    background_tasks.add_task(
        _stage_script_task,
        manga_id=str(manga_id),
        num_pages=meta.get("num_pages", num_pages),
        style=meta.get("style", style),
        language=manga.language or "ja",
    )
    return {"message": "台本の生成を開始しました"}


@router.post("/{manga_id}/storyboard/generate")
async def generate_storyboard_stage(
    manga_id: UUID,
    background_tasks: BackgroundTasks,
    current_user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db)
):
    """コマ割りを(再)生成"""
    manga = await _get_own_manga(manga_id, current_user, db)
    if not manga.script_data or manga.script_data.get("error"):
        raise HTTPException(status_code=400, detail="先に台本を生成してください")
    style = manga.script_data.get("_meta", {}).get("style", "manga")
    manga.storyboard_data = None
    await db.commit()
    background_tasks.add_task(_stage_storyboard_task, manga_id=str(manga_id), style=style)
    return {"message": "コマ割りの生成を開始しました"}


@router.put("/{manga_id}/storyboard", response_model=MangaResponse)
async def update_storyboard(
    manga_id: UUID,
    edit: ScriptEditRequest,
    current_user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db)
):
    """コマ割りを編集"""
    manga = await _get_own_manga(manga_id, current_user, db)
    manga.storyboard_data = edit.script_data
    await db.commit()
    result = await db.execute(_manga_query_with_pages().where(Manga.id == manga_id))
    return result.scalar_one()


@router.post("/{manga_id}/characters/generate")
async def generate_characters_stage(
    manga_id: UUID,
    background_tasks: BackgroundTasks,
    current_user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db)
):
    """全キャラクターのシートを生成 (未生成のみ)"""
    manga = await _get_own_manga(manga_id, current_user, db)
    if not manga.script_data or manga.script_data.get("error"):
        raise HTTPException(status_code=400, detail="先に台本を生成してください")
    style = manga.script_data.get("_meta", {}).get("style", "manga")
    background_tasks.add_task(_stage_characters_task, manga_id=str(manga_id), style=style)
    return {"message": "キャラクターシートの生成を開始しました"}


@router.post("/{manga_id}/characters/regenerate")
async def regenerate_character_stage(
    manga_id: UUID,
    body: dict,
    background_tasks: BackgroundTasks,
    current_user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db)
):
    """指定キャラクターのシートを再生成 body: {"name": "..."}"""
    from sqlalchemy.orm.attributes import flag_modified
    name = (body or {}).get("name")
    if not name:
        raise HTTPException(status_code=400, detail="nameが必要です")
    manga = await _get_own_manga(manga_id, current_user, db)
    if not manga.script_data:
        raise HTTPException(status_code=400, detail="先に台本を生成してください")
    style = manga.script_data.get("_meta", {}).get("style", "manga")
    # 対象キャラの既存シートをクリア
    for ch in manga.script_data.get("characters", []):
        if ch.get("name") == name:
            ch.pop("sheet_image_url", None)
            ch.pop("sheet_error", None)
    flag_modified(manga, "script_data")
    await db.commit()
    background_tasks.add_task(
        _stage_characters_task, manga_id=str(manga_id), style=style, only_name=name
    )
    return {"message": f"{name}のシートを再生成中です"}


@router.post("/{manga_id}/pages/generate")
async def generate_pages_stage(
    manga_id: UUID,
    background_tasks: BackgroundTasks,
    current_user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db)
):
    """本編 (全ページ) を生成"""
    manga = await _get_own_manga(manga_id, current_user, db)
    if not manga.storyboard_data or manga.storyboard_data.get("error"):
        raise HTTPException(status_code=400, detail="先にコマ割りを生成してください")
    style = (manga.script_data or {}).get("_meta", {}).get("style", "manga")
    manga.status = MangaStatus.GENERATING
    await db.commit()
    background_tasks.add_task(_stage_pages_task, manga_id=str(manga_id), style=style)
    return {"message": "本編の生成を開始しました"}


async def generate_manga_task(manga_id: str, request: MangaGenerateRequest):
    """漫画生成のバックグラウンドタスク"""
    from core.database import async_session

    async with async_session() as db:
        try:
            result = await db.execute(select(Manga).where(Manga.id == UUID(manga_id)))
            manga = result.scalar_one_or_none()

            if not manga:
                return

            # ストーリー入力を準備
            story_input = ""
            if request.creation_method == CreationMethod.URL:
                # URLからコンテンツ抽出
                content = await gpt_service.extract_content_from_url(request.source_url)
                story_input = f"""
製品/サービス: {content.get('product_name', '')}
ターゲット: {content.get('target_audience', '')}
メリット: {', '.join(content.get('key_benefits', []))}
課題: {', '.join(content.get('pain_points', []))}
CTA: {content.get('call_to_action', '')}
ストーリー概要: {content.get('suggested_story_outline', '')}
"""
            elif request.creation_method == CreationMethod.TEXT:
                story_input = request.source_text
            elif request.creation_method == CreationMethod.IMAGE:
                # 画像からキャラクター説明を生成
                story_input = request.story_outline or "画像のキャラクターを使ったストーリー"

            # 漫画生成
            result_data = await manga_composer.generate_full_manga(
                story_input=story_input,
                num_pages=request.num_pages,
                style=request.style,
                language=request.language,
                creation_method=request.creation_method.value
            )

            # データベース更新
            manga.script_data = result_data.get("script")
            manga.storyboard_data = result_data.get("storyboard")
            manga.total_pages = len(result_data.get("pages", []))
            manga.status = MangaStatus.COMPLETED

            # ページとコマを保存
            from services.storage_service import storage_service

            for page_data in result_data.get("pages", []):
                # ページ画像をSupabase Storageへアップロード
                page_image_url = None
                image_data = page_data.get("image_data")
                if image_data:
                    try:
                        page_image_url = await storage_service.upload_image(image_data, prefix="pages")
                    except Exception as upload_err:
                        print(f"Error uploading page image: {upload_err}")

                page = MangaPage(
                    manga_id=manga.id,
                    page_number=page_data.get("page_number", 1),
                    page_image_url=page_image_url,
                    page_script=str(page_data.get("panels", [])),
                    layout_data={"panels": page_data.get("panels", [])}
                )
                db.add(page)
                await db.flush()

                # コマ情報を保存 (DB側のposition系カラムはNOT NULLなのでグリッド値を入れる)
                for panel_data in page_data.get("panels", []):
                    pos = panel_data.get("position") or {}
                    panel = MangaPanel(
                        page_id=page.id,
                        panel_number=panel_data.get("panel_number", 1),
                        description=panel_data.get("description") or panel_data.get("composition"),
                        dialogue=panel_data.get("dialogues") or panel_data.get("dialogue"),
                        characters=panel_data.get("characters"),
                        image_prompt=panel_data.get("image_prompt") or panel_data.get("prompt"),
                        position_x=pos.get("column", 0),
                        position_y=pos.get("row", 0),
                        width=pos.get("span_columns", 1),
                        height=pos.get("span_rows", 1),
                    )
                    db.add(panel)

                # 1ページ目をサムネイルに設定
                if page_image_url and not manga.thumbnail_url:
                    manga.thumbnail_url = page_image_url

            await db.commit()

        except Exception as e:
            print(f"Error generating manga: {e}")
            # 失敗したトランザクションを巻き戻してからステータス更新する
            await db.rollback()
            if manga is not None:
                manga.status = MangaStatus.FAILED
                await db.commit()


@router.get("/", response_model=List[MangaListResponse])
async def list_mangas(
    skip: int = 0,
    limit: int = 20,
    current_user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db)
):
    """ユーザーの漫画一覧を取得"""
    result = await db.execute(
        select(Manga)
        .where(Manga.user_id == current_user.id)
        .order_by(Manga.created_at.desc())
        .offset(skip)
        .limit(limit)
    )
    mangas = result.scalars().all()
    return mangas


@router.get("/{manga_id}", response_model=MangaResponse)
async def get_manga(
    manga_id: UUID,
    current_user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db)
):
    """漫画の詳細を取得"""
    result = await db.execute(
        _manga_query_with_pages().where(Manga.id == manga_id, Manga.user_id == current_user.id)
    )
    manga = result.scalar_one_or_none()

    if not manga:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="漫画が見つかりません"
        )

    return manga


@router.put("/{manga_id}", response_model=MangaResponse)
async def update_manga(
    manga_id: UUID,
    manga_update: MangaUpdate,
    current_user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db)
):
    """漫画情報を更新"""
    result = await db.execute(
        _manga_query_with_pages().where(Manga.id == manga_id, Manga.user_id == current_user.id)
    )
    manga = result.scalar_one_or_none()

    if not manga:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="漫画が見つかりません"
        )

    if manga_update.title is not None:
        manga.title = manga_update.title
    if manga_update.description is not None:
        manga.description = manga_update.description

    await db.commit()
    await db.refresh(manga)

    return manga


@router.delete("/{manga_id}")
async def delete_manga(
    manga_id: UUID,
    current_user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db)
):
    """漫画を削除"""
    result = await db.execute(
        select(Manga).where(Manga.id == manga_id, Manga.user_id == current_user.id)
    )
    manga = result.scalar_one_or_none()

    if not manga:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="漫画が見つかりません"
        )

    await db.delete(manga)
    await db.commit()

    return {"message": "漫画を削除しました"}


@router.put("/{manga_id}/script", response_model=MangaResponse)
async def update_script(
    manga_id: UUID,
    script_edit: ScriptEditRequest,
    current_user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db)
):
    """台本を編集"""
    result = await db.execute(
        _manga_query_with_pages().where(Manga.id == manga_id, Manga.user_id == current_user.id)
    )
    manga = result.scalar_one_or_none()

    if not manga:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="漫画が見つかりません"
        )

    manga.script_data = script_edit.script_data
    await db.commit()
    await db.refresh(manga)

    return manga


@router.get("/{manga_id}/pages", response_model=List[MangaPageResponse])
async def get_manga_pages(
    manga_id: UUID,
    current_user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db)
):
    """漫画のページ一覧を取得"""
    result = await db.execute(
        select(Manga).where(Manga.id == manga_id, Manga.user_id == current_user.id)
    )
    manga = result.scalar_one_or_none()

    if not manga:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="漫画が見つかりません"
        )

    result = await db.execute(
        select(MangaPage)
        .options(selectinload(MangaPage.panels))
        .where(MangaPage.manga_id == manga_id)
        .order_by(MangaPage.page_number)
    )
    pages = result.scalars().all()

    return pages


@router.post("/{manga_id}/pages/{page_number}/regenerate")
async def regenerate_page(
    manga_id: UUID,
    page_number: int,
    background_tasks: BackgroundTasks,
    current_user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db)
):
    """ページを再生成"""
    result = await db.execute(
        select(Manga).where(Manga.id == manga_id, Manga.user_id == current_user.id)
    )
    manga = result.scalar_one_or_none()

    if not manga:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="漫画が見つかりません"
        )

    # TODO: バックグラウンドでページ再生成

    return {"message": f"ページ{page_number}の再生成を開始しました"}


@router.post("/{manga_id}/panels/{panel_id}/regenerate")
async def regenerate_panel(
    manga_id: UUID,
    panel_id: UUID,
    request: PanelRegenerateRequest,
    background_tasks: BackgroundTasks,
    current_user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db)
):
    """コマ画像を再生成"""
    result = await db.execute(
        select(Manga).where(Manga.id == manga_id, Manga.user_id == current_user.id)
    )
    manga = result.scalar_one_or_none()

    if not manga:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="漫画が見つかりません"
        )

    # TODO: バックグラウンドでコマ再生成

    return {"message": "コマ画像の再生成を開始しました"}


@router.get("/{manga_id}/download")
async def download_manga(
    manga_id: UUID,
    current_user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db),
):
    """漫画を PDF でダウンロード"""
    from services.pdf_service import pdf_service
    from fastapi.responses import StreamingResponse
    import io

    # 漫画を取得
    result = await db.execute(
        select(Manga).where(Manga.id == manga_id, Manga.user_id == current_user.id)
    )
    manga = result.scalar_one_or_none()

    if not manga:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="漫画が見つかりません"
        )

    # ページを取得
    result = await db.execute(
        select(MangaPage)
        .where(MangaPage.manga_id == manga_id)
        .order_by(MangaPage.page_number)
    )
    pages = result.scalars().all()

    if not pages:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="ページがありません"
        )

    # ページの画像 URL を取得
    image_urls = [page.image_url for page in pages if page.image_url]

    if not image_urls:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="画像がありません"
        )

    # PDF を生成
    pdf_bytes = await pdf_service.create_pdf_from_images(
        image_urls=image_urls,
        title=manga.title,
        author=current_user.name if current_user.name else "Unknown"
    )

    # ストリーミングレスポンス
    return StreamingResponse(
        io.BytesIO(pdf_bytes),
        media_type="application/pdf",
        headers={"Content-Disposition": f"attachment; filename={manga.title}.pdf"}
    )


@router.post("/{manga_id}/publish-booth")
async def publish_booth(
    manga_id: UUID,
    request: dict,
    background_tasks: BackgroundTasks,
    current_user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db),
):
    """BOOTH に漫画を自動出品"""
    from models.booth import BOOTHListing, BOOTHListingStatus
    from services.booth_browser_service import booth_browser_service
    import json

    # 漫画を取得
    result = await db.execute(
        select(Manga).where(Manga.id == manga_id, Manga.user_id == current_user.id)
    )
    manga = result.scalar_one_or_none()

    if not manga:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="漫画が見つかりません"
        )

    if manga.status != "completed":
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="完成した漫画のみ出品できます"
        )

    # ページを取得して PDF を生成
    result = await db.execute(
        select(MangaPage)
        .where(MangaPage.manga_id == manga_id)
        .order_by(MangaPage.page_number)
    )
    pages = result.scalars().all()

    if not pages:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="ページがありません"
        )

    image_urls = [page.image_url for page in pages if page.image_url]
    if not image_urls:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="画像がありません"
        )

    store_price = request.get("store_price", 2500)

    # BOOTH 出品情報を作成（PENDING ステータス）
    listing = BOOTHListing(
        manga_id=manga_id,
        user_id=current_user.id,
        title=manga.title,
        description=manga.description or f"{manga.title} - comicCockpit で生成された漫画",
        store_price=store_price,
        status=BOOTHListingStatus.PENDING,
    )

    db.add(listing)
    await db.commit()
    await db.refresh(listing)

    # バックグラウンドで BOOTH に出品
    async def publish_to_booth_async():
        """バックグラウンドで BOOTH に出品"""
        from core.config import settings
        from services.pdf_service import pdf_service

        try:
            # PDF を生成
            pdf_bytes = await pdf_service.create_pdf_from_images(
                image_urls=image_urls,
                title=manga.title,
                author=current_user.name or "Unknown"
            )

            # PDF を一時保存（Supabase Storage に）
            from datetime import datetime
            timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
            pdf_filename = f"booth_uploads/{listing.id}_{timestamp}.pdf"

            # Supabase Storage に保存（実装は省略）
            # pdf_url = await supabase_storage.upload(pdf_filename, pdf_bytes)

            # Playwright で BOOTH に出品
            await booth_browser_service.initialize()

            # ログイン
            login_success = await booth_browser_service.login(
                settings.booth_email,
                settings.booth_password
            )

            if not login_success:
                raise Exception("BOOTH login failed")

            # 商品を作成
            result = await booth_browser_service.create_product(
                title=manga.title,
                description=manga.description or f"{manga.title} - AI 生成漫画",
                price=store_price,
                pdf_url="https://example.com/temp.pdf"  # 実装では PDF URL を使用
            )

            if result.get("success"):
                # DB を更新
                async_session = db
                listing.booth_item_id = result.get("item_id")
                listing.booth_url = result.get("booth_url")
                listing.status = BOOTHListingStatus.PUBLISHED
                listing.booth_response = json.dumps(result)
                await async_session.commit()

                print(f"Successfully published to BOOTH: {listing.id}")
            else:
                raise Exception(f"Failed to create product: {result.get('error')}")

        except Exception as e:
            print(f"Error publishing to BOOTH: {e}")
            listing.status = BOOTHListingStatus.DRAFT
            await db.commit()
        finally:
            await booth_browser_service.close()

    # バックグラウンドタスクに追加
    background_tasks.add_task(publish_to_booth_async)

    return {
        "success": True,
        "message": "BOOTH への自動出品を開始しました",
        "listing_id": str(listing.id),
        "seller_commission": int(store_price * 0.6),
        "store_commission": int(store_price * 0.4),
    }
