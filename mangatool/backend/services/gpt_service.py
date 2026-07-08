"""
台本・脚本生成サービス (Gemini 3.5 Flash)
"""
import json
import asyncio
from typing import Optional
from google import genai
from google.genai import types
from tenacity import retry, stop_after_attempt, wait_exponential
import httpx
from bs4 import BeautifulSoup

from core.config import settings


class GPTService:
    """Geminiを使用した台本・脚本生成サービス"""

    def __init__(self):
        self.client = genai.Client(api_key=settings.google_genai_api_key)
        self.model = settings.gemini_text_model

    async def _json_completion(
        self,
        system_prompt: str,
        user_prompt: str,
        temperature: float = 0.7,
        max_tokens: int = 32000,
    ) -> dict:
        """
        JSONを返すGemini補完の共通処理。
        response_mime_type=application/json で構造化出力し、
        万一壊れたJSONは可能な範囲で修復する。
        """
        def _call():
            return self.client.models.generate_content(
                model=self.model,
                contents=[user_prompt],
                config=types.GenerateContentConfig(
                    system_instruction=system_prompt,
                    temperature=temperature,
                    max_output_tokens=max_tokens,
                    response_mime_type="application/json",
                ),
            )

        # genaiクライアントは同期APIなのでスレッドに逃がしてイベントループを塞がない
        response = await asyncio.to_thread(_call)
        content = response.text or ""

        try:
            return json.loads(content)
        except json.JSONDecodeError:
            # 途中で切れた等で壊れたJSONを修復して再パース
            repaired = self._repair_json(content)
            return json.loads(repaired)

    @staticmethod
    def _repair_json(text: str) -> str:
        """
        途中で切れたJSON文字列を補修する。
        末尾の未完トークンを削り、開いたままの [ { " を閉じる。
        """
        text = text.strip()
        # コードフェンス除去
        if text.startswith("```"):
            text = text.split("```", 2)[-1] if text.count("```") >= 2 else text.strip("`")
        start = text.find("{")
        if start > 0:
            text = text[start:]

        # 文字列の途中で切れている場合、最後の完全な要素まで巻き戻す
        # 末尾から見て、直近の '}' か ']' までを有効範囲とする
        last_obj = max(text.rfind("}"), text.rfind("]"))
        if last_obj != -1:
            text = text[: last_obj + 1]

        # 開いたままの括弧をスタックで数えて閉じる
        stack = []
        in_str = False
        escape = False
        for ch in text:
            if escape:
                escape = False
                continue
            if ch == "\\":
                escape = True
                continue
            if ch == '"':
                in_str = not in_str
                continue
            if in_str:
                continue
            if ch in "{[":
                stack.append(ch)
            elif ch == "}" and stack and stack[-1] == "{":
                stack.pop()
            elif ch == "]" and stack and stack[-1] == "[":
                stack.pop()

        if in_str:
            text += '"'
        for opener in reversed(stack):
            text += "}" if opener == "{" else "]"
        return text

    @retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
    async def generate_script(
        self,
        story_input: str,
        num_pages: int = 8,
        style: str = "manga",
        language: str = "ja",
        progress_callback=None
    ) -> dict:
        """
        ストーリー入力から台本を生成（250ページまで対応・30ページごと分割）

        Args:
            story_input: ストーリーの概要またはURLの内容
            num_pages: 生成するページ数（1～250）
            style: 漫画スタイル（manga, webtoon, comic）
            language: 言語（ja, en, zh, ko）
            progress_callback: 進捗コールバック関数（percent: 0-100）

        Returns:
            台本データ（JSON形式）
        """
        # 短編（50ページ以下）は一括生成
        if num_pages <= 50:
            if progress_callback:
                await progress_callback(10)
            system_prompt = self._get_script_system_prompt(style, language)
            user_prompt = self._get_script_user_prompt(story_input, num_pages, language)
            result = await self._json_completion(system_prompt, user_prompt, temperature=0.7)
            if progress_callback:
                await progress_callback(100)
            return result

        # 長編（50ページ超）は30ページごと分割生成
        print(f"長編制作: {num_pages}ページを30ページごと分割して生成開始")
        segments = []
        segment_size = 30
        total_segments = (num_pages + segment_size - 1) // segment_size

        for i in range(total_segments):
            start_page = i * segment_size + 1
            end_page = min((i + 1) * segment_size, num_pages)
            segment_pages = end_page - start_page + 1

            # 分割プロンプト
            context = ""
            if i > 0:
                prev_segment = segments[-1]
                prev_synopsis = prev_segment.get("synopsis", "")
                context = f"\n【前セクション概要】{prev_synopsis}\n【前セクションの続きを作成してください】"

            segment_prompt = f"""
{story_input}

{context}

【セクション {i+1}/{total_segments}】
ページ {start_page} 〜 {end_page}（{segment_pages}ページ分）の台本を生成してください。
"""

            system_prompt = self._get_script_system_prompt(style, language)
            user_prompt = self._get_script_user_prompt(segment_prompt, segment_pages, language)
            segment = await self._json_completion(system_prompt, user_prompt, temperature=0.7)
            segments.append(segment)

            # 進捗表示
            progress = int(10 + (i + 1) / total_segments * 80)  # 10%～90%
            if progress_callback:
                await progress_callback(progress)
            print(f"  セクション {i+1}/{total_segments} 完成: ページ {start_page}～{end_page}")

        # セグメント統合
        if progress_callback:
            await progress_callback(90)

        merged = {
            "title": segments[0].get("title", "無題"),
            "synopsis": "",
            "logline": segments[0].get("logline", ""),
            "pages": [],
            "characters": segments[0].get("characters", []),
        }

        for segment in segments:
            merged["pages"].extend(segment.get("pages", []))
            merged["synopsis"] += segment.get("synopsis", "") + "\n"

            # キャラクターをマージ（重複排除）
            existing_names = {c.get("name") for c in merged["characters"]}
            for char in segment.get("characters", []):
                if char.get("name") not in existing_names:
                    merged["characters"].append(char)
                    existing_names.add(char.get("name"))

        merged["synopsis"] = merged["synopsis"].strip()

        if progress_callback:
            await progress_callback(100)

        print(f"✅ 長編台本完成: 全{num_pages}ページ, {len(merged['pages'])}ページ分のセリフ, キャラクター{len(merged['characters'])}人")
        return merged

    @retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
    async def generate_storyboard(
        self,
        script_data: dict,
        style: str = "manga",
        progress_callback=None
    ) -> dict:
        """
        台本から台割り（ストーリーボード）を生成（250ページまで対応・ページ数に応じて分割）

        Args:
            script_data: 台本データ
            style: 漫画スタイル
            progress_callback: 進捗コールバック関数（percent: 0-100）

        Returns:
            台割りデータ（JSON形式）
        """
        pages = script_data.get("pages", [])
        total_pages = len(pages)

        # 少ページ（30ページ以下）は一括生成
        if total_pages <= 30:
            if progress_callback:
                await progress_callback(20)
            system_prompt = self._get_storyboard_system_prompt(style)
            user_prompt = f"""
以下の台本から台割り（ストーリーボード）を作成してください。
各ページのコマ割り、キャラクター配置、構図を詳細に指定してください。

必須要件:
- 各ページ必ず4〜6コマにすること（見開き・見せ場のみ1〜3コマ可、全体の2割まで）
- 台本にある全てのセリフを、いずれかのコマに漏れなく割り当てること
- position は2列グリッド: column は 1〜2、row は 1〜4、span_columns は 1〜2
- 大ゴマ (size=large) は span_columns=2 にすること
- 同じページ内でコマ同士が重ならないようにすること

台本データ:
{json.dumps(script_data, ensure_ascii=False, indent=2)}
"""
            result = await self._json_completion(system_prompt, user_prompt, temperature=0.7)
            if progress_callback:
                await progress_callback(100)
            return result

        # 長編（30ページ超）はセクション分割生成
        print(f"長編コマ割り: {total_pages}ページをセクション分割生成")
        segments = []
        segment_size = 30
        total_segments = (total_pages + segment_size - 1) // segment_size

        for i in range(total_segments):
            start_idx = i * segment_size
            end_idx = min((i + 1) * segment_size, total_pages)
            segment_pages = pages[start_idx:end_idx]

            # セクション用台本データを作成
            section_script = {
                "title": script_data.get("title"),
                "pages": segment_pages,
                "characters": script_data.get("characters", []),
            }

            system_prompt = self._get_storyboard_system_prompt(style)
            user_prompt = f"""
【セクション {i+1}/{total_segments}】ページ {start_idx+1}～{end_idx}

以下の台本セクションから台割り（ストーリーボード）を作成してください。
各ページのコマ割り、キャラクター配置、構図を詳細に指定してください。

必須要件:
- 各ページ必ず4〜6コマにすること（見開き・見せ場のみ1〜3コマ可、全体の2割まで）
- 台本にある全てのセリフを、いずれかのコマに漏れなく割り当てること
- position は2列グリッド: column は 1〜2、row は 1〜4、span_columns は 1〜2
- 大ゴマ (size=large) は span_columns=2 にすること
- 同じページ内でコマ同士が重ならないようにすること

台本データ:
{json.dumps(section_script, ensure_ascii=False, indent=2)}
"""
            segment = await self._json_completion(system_prompt, user_prompt, temperature=0.7)
            segments.append(segment)

            # 進捗表示
            progress = int(20 + (i + 1) / total_segments * 70)  # 20%～90%
            if progress_callback:
                await progress_callback(progress)
            print(f"  セクション {i+1}/{total_segments} 完成: ページ {start_idx+1}～{end_idx}")

        # セグメント統合
        if progress_callback:
            await progress_callback(95)

        merged = {
            "pages": [],
            "characters": script_data.get("characters", []),
        }

        for segment in segments:
            merged["pages"].extend(segment.get("pages", []))

        if progress_callback:
            await progress_callback(100)

        print(f"✅ コマ割り完成: {total_pages}ページ")
        return merged

    @retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
    async def generate_image_prompts(
        self,
        storyboard_data: dict,
        style: str = "manga"
    ) -> list[dict]:
        """
        台割りから画像生成用プロンプトを生成

        Args:
            storyboard_data: 台割りデータ
            style: 漫画スタイル

        Returns:
            各コマの画像生成プロンプトリスト
        """
        system_prompt = """
あなたは漫画の画像生成AIのためのプロンプトエンジニアです。
台割りデータから、Google Imagen（画像生成AI）用の高品質なプロンプトを生成してください。

プロンプトは以下の形式で出力してください:
- 英語で記述
- 画風、構図、キャラクター、背景、表情、効果を含む
- 漫画・アニメスタイルを明示
- ネガティブプロンプトも含める

JSON形式で出力:
{
    "panels": [
        {
            "page": 1,
            "panel": 1,
            "prompt": "...",
            "negative_prompt": "...",
            "style_keywords": ["..."]
        }
    ]
}
"""

        user_prompt = f"""
以下の台割りから各コマの画像生成プロンプトを作成してください:

{json.dumps(storyboard_data, ensure_ascii=False, indent=2)}
"""
        prompts_data = await self._json_completion(system_prompt, user_prompt, temperature=0.5)
        return prompts_data.get("panels", [])

    async def _fetch_webpage(self, url: str) -> str:
        """
        URLからWebページのコンテンツを取得

        Args:
            url: 取得対象のURL

        Returns:
            抽出されたテキストコンテンツ
        """
        headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
        }

        async with httpx.AsyncClient(follow_redirects=True, timeout=30.0) as client:
            response = await client.get(url, headers=headers)
            response.raise_for_status()
            html = response.text

        # BeautifulSoupでパース
        soup = BeautifulSoup(html, 'lxml')

        # 不要な要素を削除
        for element in soup(['script', 'style', 'nav', 'footer', 'header', 'aside', 'noscript']):
            element.decompose()

        # メタ情報を抽出
        meta_info = {
            "title": soup.title.string if soup.title else "",
            "description": "",
            "keywords": ""
        }

        # メタタグから情報取得
        desc_meta = soup.find('meta', attrs={'name': 'description'})
        if desc_meta:
            meta_info["description"] = desc_meta.get('content', '')

        keywords_meta = soup.find('meta', attrs={'name': 'keywords'})
        if keywords_meta:
            meta_info["keywords"] = keywords_meta.get('content', '')

        # OGP情報
        og_title = soup.find('meta', property='og:title')
        og_desc = soup.find('meta', property='og:description')
        if og_title:
            meta_info["og_title"] = og_title.get('content', '')
        if og_desc:
            meta_info["og_description"] = og_desc.get('content', '')

        # 本文テキストを抽出
        text_content = soup.get_text(separator='\n', strip=True)

        # テキストを整形（長すぎる場合は切り詰め）
        lines = [line.strip() for line in text_content.split('\n') if line.strip()]
        text_content = '\n'.join(lines[:200])  # 最初の200行まで

        # 結果をまとめる
        result = f"""
【ページタイトル】
{meta_info.get('title', 'タイトルなし')}

【メタ説明】
{meta_info.get('description', '')}

【OGP情報】
タイトル: {meta_info.get('og_title', '')}
説明: {meta_info.get('og_description', '')}

【ページコンテンツ】
{text_content[:8000]}
"""
        return result

    @retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
    async def extract_content_from_url(self, url: str) -> dict:
        """
        URLから広告コンテンツを抽出・分析

        Args:
            url: 分析対象のURL

        Returns:
            抽出されたコンテンツ情報
        """
        # 実際にWebページを取得
        try:
            page_content = await self._fetch_webpage(url)
        except Exception as e:
            print(f"Warning: Failed to fetch URL {url}: {e}")
            page_content = f"URLの取得に失敗しました。URL: {url}"

        system_prompt = """
あなたは広告コンテンツ分析の専門家です。
与えられたWebページの内容から、漫画広告に変換するための要素を抽出してください。

以下の情報をJSON形式で出力:
{
    "product_name": "製品/サービス名",
    "target_audience": "ターゲット層",
    "key_benefits": ["メリット1", "メリット2", "メリット3"],
    "pain_points": ["課題1", "課題2"],
    "call_to_action": "CTA（行動喚起）",
    "tone": "トーン（明るい、真剣、カジュアル、etc）",
    "main_message": "メインメッセージ",
    "suggested_story_outline": "漫画化するためのストーリー概要（起承転結を意識）"
}
"""

        user_prompt = f"""
以下のWebページの内容を分析し、漫画広告用のコンテンツを抽出してください:

URL: {url}

【取得したページ内容】
{page_content}
"""
        return await self._json_completion(system_prompt, user_prompt, temperature=0.7)

    def _get_script_system_prompt(self, style: str, language: str) -> str:
        """台本生成用システムプロンプトを取得"""
        return f"""
あなたはプロの漫画脚本家です。
魅力的でわかりやすい漫画の台本を作成してください。

スタイル: {style}
言語: {language}

以下のJSON形式で出力してください:
{{
    "title": "タイトル",
    "synopsis": "あらすじ",
    "characters": [
        {{
            "name": "キャラクター名",
            "description": "外見・性格の説明",
            "role": "主人公/脇役/敵"
        }}
    ],
    "pages": [
        {{
            "page_number": 1,
            "scene_description": "シーン説明",
            "panels": [
                {{
                    "panel_number": 1,
                    "description": "コマの説明",
                    "dialogues": [
                        {{
                            "character": "キャラクター名",
                            "text": "セリフ",
                            "type": "normal/thought/narration"
                        }}
                    ],
                    "sound_effects": ["効果音"]
                }}
            ]
        }}
    ]
}}
"""

    def _get_script_user_prompt(self, story_input: str, num_pages: int, language: str) -> str:
        """台本生成用ユーザープロンプトを取得"""
        return f"""
以下の内容を基に、{num_pages}ページの漫画台本を作成してください。

ストーリー/内容:
{story_input}

要件:
- {num_pages}ページで完結する構成
- 各ページは必ず4〜6コマにすること（クライマックスの大ゴマページのみ2〜3コマ可、全体の2割まで）
- 起承転結を意識した構成
- キャラクターの表情や動きが想像できる描写
- {language}で出力
"""

    def _get_storyboard_system_prompt(self, style: str) -> str:
        """台割り生成用システムプロンプトを取得"""
        return f"""
あなたはプロの漫画演出家です。
台本を基に、具体的な台割り（ストーリーボード）を作成してください。

スタイル: {style}

以下のJSON形式で出力してください:
{{
    "pages": [
        {{
            "page_number": 1,
            "layout_type": "standard/splash/spread",
            "panels": [
                {{
                    "panel_number": 1,
                    "size": "large/medium/small",
                    "position": {{
                        "row": 1,
                        "column": 1,
                        "span_rows": 1,
                        "span_columns": 1
                    }},
                    "camera_angle": "close-up/medium/wide/bird's-eye",
                    "composition": "構図の説明",
                    "characters": [
                        {{
                            "name": "キャラクター名",
                            "position": "left/center/right",
                            "expression": "表情",
                            "pose": "ポーズ"
                        }}
                    ],
                    "background": "背景の説明",
                    "effects": ["集中線", "トーン"],
                    "dialogues": [
                        {{
                            "character": "キャラクター名",
                            "text": "セリフ",
                            "bubble_position": "top/bottom/left/right"
                        }}
                    ]
                }}
            ]
        }}
    ]
}}

【コマ割り(position)の絶対ルール — 最重要】
漫画はコマ割りが命です。以下を厳守してください:
- グリッドは必ず「2列」。column は 1 または 2 のみ。
- row は 1 から順に連番。各ページ 2〜4 行に収める。
- 各行は必ず埋めること。埋め方は次の2択のみ:
  (A) その行に横並び2コマ → それぞれ column=1 と column=2、span_columns=1
  (B) その行に横長1コマ → column=1, span_columns=2 (ぶち抜き大ゴマ)
- コマ同士が絶対に重ならないこと。空きセルを作らないこと。
- size=large のコマは必ず span_columns=2 (横ぶち抜き) にすること。
- 例(5コマ): 1行目[コマ1 large span2] / 2行目[コマ2, コマ3] / 3行目[コマ4, コマ5]
- panel_number は読む順（右上→左下ではなく、上から下・左から右）で振ること。
"""


# シングルトンインスタンス
gpt_service = GPTService()
