"use client";

import { useState } from "react";
import {
  Link as LinkIcon,
  Image as ImageIcon,
  FileText,
  Upload,
  AlertCircle,
  Sparkles,
  Globe,
  Palette,
  BookOpen,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useGenerationStore } from "@/stores/generation";
import { cn } from "@/lib/utils";
import type { CreationMethod } from "@/types";

const METHODS: { id: CreationMethod; label: string; icon: React.ElementType; description: string }[] = [
  {
    id: "url",
    label: "URL貼付",
    icon: LinkIcon,
    description: "LPや広告ページのURLから自動で漫画を生成",
  },
  {
    id: "image",
    label: "キャラ画像",
    icon: ImageIcon,
    description: "キャラクター画像をアップロードして漫画を生成",
  },
  {
    id: "text",
    label: "テキスト",
    icon: FileText,
    description: "あらすじやストーリーをテキストで入力して生成",
  },
];

const LANGUAGES = [
  { value: "ja", label: "日本語" },
  { value: "en", label: "English" },
  { value: "zh", label: "中文" },
  { value: "ko", label: "한국어" },
];

const STYLES = [
  { value: "manga", label: "漫画", description: "モノクロ・スクリーントーン" },
  { value: "webtoon", label: "Webtoon", description: "縦スクロール・フルカラー" },
  { value: "comic", label: "コミック", description: "アメコミ風" },
  { value: "anime", label: "アニメ", description: "アニメイラスト風" },
];

interface InputStepProps {
  onNext: () => void;
}

export function InputStep({ onNext }: InputStepProps) {
  const { formData, setFormField, setFormData } = useGenerationStore();
  const [dragOver, setDragOver] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleMethodSelect = (method: CreationMethod) => {
    setFormField("method", method);
    setError(null);
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = Array.from(e.target.files || []);
    setFormField("sourceImages", files);
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setDragOver(false);
    const files = Array.from(e.dataTransfer.files).filter((f) =>
      f.type.startsWith("image/")
    );
    if (files.length > 0) {
      setFormField("sourceImages", files);
    }
  };

  const validateAndProceed = () => {
    // バリデーション
    if (!formData.title.trim()) {
      setError("タイトルを入力してください");
      return;
    }

    if (formData.method === "url" && !formData.sourceUrl.trim()) {
      setError("URLを入力してください");
      return;
    }

    if (formData.method === "text" && !formData.sourceText.trim()) {
      setError("あらすじを入力してください");
      return;
    }

    if (formData.method === "image" && formData.sourceImages.length === 0) {
      setError("キャラクター画像をアップロードしてください");
      return;
    }

    setError(null);
    onNext();
  };

  return (
    <div className="max-w-3xl mx-auto space-y-8">
      {/* タイトル入力 */}
      <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div className="flex items-center gap-3 mb-4">
          <div className="w-10 h-10 rounded-xl bg-primary-50 flex items-center justify-center">
            <Sparkles className="w-5 h-5 text-primary-500" />
          </div>
          <div>
            <h3 className="font-semibold text-gray-900">作品タイトル</h3>
            <p className="text-sm text-gray-500">漫画のタイトルを入力してください</p>
          </div>
        </div>
        <Input
          type="text"
          placeholder="例: 新商品PRマンガ"
          value={formData.title}
          onChange={(e) => setFormField("title", e.target.value)}
          className="h-12 text-base"
        />
      </div>

      {/* 制作方法選択 */}
      <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div className="flex items-center gap-3 mb-6">
          <div className="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
            <BookOpen className="w-5 h-5 text-blue-500" />
          </div>
          <div>
            <h3 className="font-semibold text-gray-900">制作方法</h3>
            <p className="text-sm text-gray-500">漫画の元となる素材を選択してください</p>
          </div>
        </div>

        {/* メソッド選択カード */}
        <div className="grid grid-cols-3 gap-4 mb-6">
          {METHODS.map((method) => (
            <button
              key={method.id}
              onClick={() => handleMethodSelect(method.id)}
              className={cn(
                "p-4 rounded-xl border-2 transition-all text-left",
                formData.method === method.id
                  ? "border-primary-500 bg-primary-50"
                  : "border-gray-200 hover:border-gray-300 hover:bg-gray-50"
              )}
            >
              <method.icon
                className={cn(
                  "w-6 h-6 mb-3",
                  formData.method === method.id
                    ? "text-primary-500"
                    : "text-gray-400"
                )}
              />
              <div
                className={cn(
                  "font-medium mb-1",
                  formData.method === method.id
                    ? "text-primary-700"
                    : "text-gray-900"
                )}
              >
                {method.label}
              </div>
              <div className="text-xs text-gray-500">{method.description}</div>
            </button>
          ))}
        </div>

        {/* メソッド別入力 */}
        <div className="border-t border-gray-100 pt-6">
          {formData.method === "url" && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                LP/広告ページURL
              </label>
              <Input
                type="url"
                placeholder="https://example.com/lp"
                value={formData.sourceUrl}
                onChange={(e) => setFormField("sourceUrl", e.target.value)}
                className="h-12"
              />
              <p className="text-xs text-gray-500 mt-2">
                URLの内容を自動解析し、製品・サービスの特徴を漫画化します
              </p>
            </div>
          )}

          {formData.method === "image" && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                キャラクター画像
              </label>
              <div
                className={cn(
                  "border-2 border-dashed rounded-xl p-8 text-center transition-colors cursor-pointer",
                  dragOver
                    ? "border-primary-500 bg-primary-50"
                    : "border-gray-200 hover:border-gray-300"
                )}
                onDragOver={(e) => {
                  e.preventDefault();
                  setDragOver(true);
                }}
                onDragLeave={() => setDragOver(false)}
                onDrop={handleDrop}
              >
                <input
                  type="file"
                  multiple
                  accept="image/*"
                  className="hidden"
                  id="image-upload"
                  onChange={handleFileChange}
                />
                <label htmlFor="image-upload" className="cursor-pointer">
                  <Upload className="w-10 h-10 text-gray-400 mx-auto mb-3" />
                  <p className="text-sm text-gray-600 mb-1">
                    クリックまたはドラッグ&ドロップ
                  </p>
                  <p className="text-xs text-gray-400">
                    PNG, JPG, WEBP（最大10MB）
                  </p>
                  {formData.sourceImages.length > 0 && (
                    <p className="text-sm text-primary-600 mt-3 font-medium">
                      {formData.sourceImages.length}ファイル選択済み
                    </p>
                  )}
                </label>
              </div>
            </div>
          )}

          {formData.method === "text" && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                あらすじ・ストーリー
              </label>
              <textarea
                placeholder="漫画のあらすじやストーリーを入力してください...&#10;&#10;例: 主人公の田中太郎は新入社員。仕事で失敗続きだったが、先輩に励まされて成長し、最終的にプロジェクトを成功させる。"
                value={formData.sourceText}
                onChange={(e) => setFormField("sourceText", e.target.value)}
                className="w-full h-40 px-4 py-3 border border-gray-200 rounded-xl text-base resize-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
          )}
        </div>
      </div>

      {/* 長編制作ガイド */}
      {formData.numPages > 50 && (
        <div className="bg-blue-50 border border-blue-200 rounded-2xl p-6">
          <div className="flex gap-4">
            <div className="w-10 h-10 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold flex-shrink-0 text-lg">
              📖
            </div>
            <div>
              <h3 className="font-semibold text-blue-900 mb-2">長編制作モード</h3>
              <p className="text-sm text-blue-800 mb-3">
                {formData.numPages}ページの長編漫画を作成します。生成に時間がかかりますが、以下の機能が自動で行われます：
              </p>
              <ul className="text-sm text-blue-800 space-y-1 ml-4 list-disc">
                <li>キャラクター設定の自動継承（全ページで統一）</li>
                <li>ストーリー構成の自動最適化</li>
                <li>表紙の自動生成（オプション）</li>
                <li>各ページの画像品質を自動調整</li>
              </ul>
              <p className="text-xs text-blue-700 mt-3 font-medium">
                ⏱️ 目安: {formData.numPages}ページ = 約 {Math.ceil(formData.numPages / 5)} 〜 {Math.ceil(formData.numPages * 1.5)} 分
              </p>
            </div>
          </div>
        </div>
      )}

      {/* 詳細設定 */}
      <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div className="grid grid-cols-2 lg:grid-cols-3 gap-6">
          {/* ページ数 */}
          <div className="lg:col-span-1">
            <div className="flex items-center gap-2 mb-3">
              <BookOpen className="w-4 h-4 text-gray-400" />
              <label className="text-sm font-medium text-gray-700">ページ数</label>
            </div>
            <select
              value={formData.numPages}
              onChange={(e) => setFormField("numPages", Number(e.target.value))}
              className="w-full h-11 px-3 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500"
            >
              {/* 短編 */}
              <optgroup label="短編（低速）">
                {[1, 2, 4, 6, 8, 12, 16].map((n) => (
                  <option key={n} value={n}>
                    {n}ページ
                  </option>
                ))}
              </optgroup>
              {/* 中編 */}
              <optgroup label="中編">
                {[20, 32, 50].map((n) => (
                  <option key={n} value={n}>
                    {n}ページ
                  </option>
                ))}
              </optgroup>
              {/* 長編 */}
              <optgroup label="長編（最大250ページ）">
                {[75, 100, 150, 200, 250].map((n) => (
                  <option key={n} value={n}>
                    {n}ページ
                  </option>
                ))}
              </optgroup>
            </select>
            <p className="text-xs text-gray-400 mt-1">
              短編: 1p/1-2分 | 長編: 1p/5-10分
            </p>
          </div>

          {/* 言語 */}
          <div>
            <div className="flex items-center gap-2 mb-3">
              <Globe className="w-4 h-4 text-gray-400" />
              <label className="text-sm font-medium text-gray-700">言語</label>
            </div>
            <select
              value={formData.language}
              onChange={(e) => setFormField("language", e.target.value)}
              className="w-full h-11 px-3 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500"
            >
              {LANGUAGES.map((lang) => (
                <option key={lang.value} value={lang.value}>
                  {lang.label}
                </option>
              ))}
            </select>
          </div>

          {/* スタイル */}
          <div>
            <div className="flex items-center gap-2 mb-3">
              <Palette className="w-4 h-4 text-gray-400" />
              <label className="text-sm font-medium text-gray-700">スタイル</label>
            </div>
            <select
              value={formData.style}
              onChange={(e) =>
                setFormField("style", e.target.value as "manga" | "webtoon" | "comic" | "anime")
              }
              className="w-full h-11 px-3 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500"
            >
              {STYLES.map((style) => (
                <option key={style.value} value={style.value}>
                  {style.label} - {style.description}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {/* 表紙設定 */}
      <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
              <BookOpen className="w-5 h-5 text-amber-500" />
            </div>
            <div>
              <h3 className="font-semibold text-gray-900">表紙（カバー）</h3>
              <p className="text-sm text-gray-500">
                本編の前に表紙を1ページ追加します（本編{formData.numPages}ページ + 表紙）
              </p>
            </div>
          </div>
          {/* 表紙を付けるトグル */}
          <button
            type="button"
            onClick={() => setFormField("makeCover", !formData.makeCover)}
            className={cn(
              "relative w-12 h-7 rounded-full transition-colors",
              formData.makeCover ? "bg-primary-500" : "bg-gray-300"
            )}
            aria-pressed={formData.makeCover}
          >
            <span
              className={cn(
                "absolute top-1 w-5 h-5 rounded-full bg-white transition-transform",
                formData.makeCover ? "translate-x-6" : "translate-x-1"
              )}
            />
          </button>
        </div>

        {formData.makeCover && (
          <div className="border-t border-gray-100 pt-4">
            <label className="text-sm font-medium text-gray-700 mb-2 block">表紙の配色</label>
            <div className="grid grid-cols-2 gap-3">
              {[
                { value: true, label: "カラー", desc: "華やかで目を引く表紙" },
                { value: false, label: "白黒（モノクロ）", desc: "本編と統一した雰囲気" },
              ].map((opt) => (
                <button
                  key={String(opt.value)}
                  type="button"
                  onClick={() => setFormField("coverColored", opt.value)}
                  className={cn(
                    "p-4 rounded-xl border-2 transition-all text-left",
                    formData.coverColored === opt.value
                      ? "border-primary-500 bg-primary-50"
                      : "border-gray-200 hover:border-gray-300"
                  )}
                >
                  <div
                    className={cn(
                      "font-medium mb-1",
                      formData.coverColored === opt.value ? "text-primary-700" : "text-gray-900"
                    )}
                  >
                    {opt.label}
                  </div>
                  <div className="text-xs text-gray-500">{opt.desc}</div>
                </button>
              ))}
            </div>
          </div>
        )}
      </div>

      {/* エラー表示 */}
      {error && (
        <div className="bg-red-50 border border-red-200 rounded-xl p-4 flex items-center gap-3">
          <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0" />
          <p className="text-sm text-red-700">{error}</p>
        </div>
      )}

      {/* 次へボタン */}
      <div className="flex justify-end">
        <Button
          onClick={validateAndProceed}
          size="lg"
          className="h-12 px-8 text-base"
        >
          生成を開始
        </Button>
      </div>
    </div>
  );
}
