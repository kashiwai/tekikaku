"use client";

import { useState } from "react";
import { Loader2, X } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";

interface PublishBoothModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (price: number) => Promise<void>;
  mangaTitle: string;
  pageCount: number;
}

export function PublishBoothModal({
  isOpen,
  onClose,
  onSubmit,
  mangaTitle,
  pageCount,
}: PublishBoothModalProps) {
  const [price, setPrice] = useState(2500);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async () => {
    setIsSubmitting(true);
    try {
      await onSubmit(price);
      onClose();
    } finally {
      setIsSubmitting(false);
    }
  };

  // 参考価格の計算
  const getPriceRecommendation = (pages: number): { min: number; max: number } => {
    if (pages < 50) return { min: 500, max: 1000 };
    if (pages < 100) return { min: 1000, max: 1500 };
    if (pages < 150) return { min: 1500, max: 2500 };
    if (pages < 200) return { min: 2000, max: 3500 };
    return { min: 3000, max: 5000 };
  };

  const recommendation = getPriceRecommendation(pageCount);
  const sellerCommission = Math.floor(price * 0.6);
  const boothFee = Math.floor(price * 0.1);

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <Card className="w-full max-w-md">
        <div className="flex items-center justify-between p-6 border-b">
          <h2 className="text-lg font-semibold">BOOTH で販売</h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <CardContent className="p-6 space-y-6">
          {/* 作品情報 */}
          <div>
            <h3 className="font-semibold text-gray-900 mb-2">作品情報</h3>
            <div className="bg-gray-50 p-4 rounded-lg space-y-2">
              <p>
                <span className="text-gray-600">タイトル：</span>
                <span className="font-medium">{mangaTitle}</span>
              </p>
              <p>
                <span className="text-gray-600">ページ数：</span>
                <span className="font-medium">{pageCount} ページ</span>
              </p>
              <p>
                <span className="text-gray-600">出版社：</span>
                <span className="font-medium">comicCockpit 出版</span>
              </p>
            </div>
          </div>

          {/* 参考価格 */}
          <div>
            <h3 className="font-semibold text-gray-900 mb-2">参考価格</h3>
            <div className="bg-blue-50 border border-blue-200 p-4 rounded-lg">
              <p className="text-sm text-gray-600">
                {pageCount} ページの漫画の市場相場：
              </p>
              <p className="text-lg font-bold text-blue-600 mt-1">
                ¥{recommendation.min.toLocaleString()} ～ ¥{recommendation.max.toLocaleString()}
              </p>
              <p className="text-xs text-gray-500 mt-2">
                ※ 参考価格です。実際の販売価格はご自由に設定できます
              </p>
            </div>
          </div>

          {/* 価格入力 */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              販売価格（円）
            </label>
            <input
              type="number"
              min="100"
              max="50000"
              step="100"
              value={price}
              onChange={(e) => setPrice(Math.max(100, parseInt(e.target.value) || 0))}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <p className="text-xs text-gray-500 mt-1">
              最小：¥100 ～ 最大：¥50,000
            </p>
          </div>

          {/* 配分シミュレーション */}
          <div>
            <h3 className="font-semibold text-gray-900 mb-2">販売額の配分</h3>
            <div className="space-y-2 bg-gray-50 p-4 rounded-lg">
              <div className="flex justify-between items-center">
                <span className="text-gray-600">販売価格</span>
                <span className="font-bold text-lg">¥{price.toLocaleString()}</span>
              </div>
              <hr />
              <div className="flex justify-between items-center text-sm">
                <span className="text-gray-600">BOOTH 手数料（10%）</span>
                <span className="text-red-600">-¥{boothFee.toLocaleString()}</span>
              </div>
              <hr />
              <div className="flex justify-between items-center font-semibold">
                <span className="text-gray-900">あなたの獲得額（60%）</span>
                <span className="text-green-600 text-lg">
                  ¥{sellerCommission.toLocaleString()}
                </span>
              </div>
              <p className="text-xs text-gray-500 mt-2">
                mangatool 事業者は 40% を獲得します
              </p>
            </div>
          </div>

          {/* ボタン */}
          <div className="flex gap-3">
            <Button
              variant="outline"
              onClick={onClose}
              disabled={isSubmitting}
              className="flex-1"
            >
              キャンセル
            </Button>
            <Button
              onClick={handleSubmit}
              disabled={isSubmitting}
              className="flex-1 bg-blue-600 hover:bg-blue-700"
            >
              {isSubmitting ? (
                <>
                  <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                  出品中...
                </>
              ) : (
                "BOOTH で出品"
              )}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
