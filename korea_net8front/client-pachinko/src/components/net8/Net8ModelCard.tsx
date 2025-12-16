"use client";

import Link from "next/link";

interface Net8ModelCardProps {
  id: string;
  name: string;
  category: "pachinko" | "slot";
  thumbnail?: string;
  minPoints?: number;
}

export function Net8ModelCard({ id, name, category, thumbnail, minPoints = 100 }: Net8ModelCardProps) {
  return (
    <Link
      href={`/pachinko/play/${id}`}
      className="group relative overflow-hidden rounded-xl bg-card border hover:border-primary/50 transition-all duration-300"
    >
      {/* Thumbnail */}
      <div className="aspect-[4/3] bg-gradient-to-br from-purple-900 to-blue-900 relative overflow-hidden">
        {thumbnail ? (
          <img src={thumbnail} alt={name} className="w-full h-full object-cover" />
        ) : (
          <div className="absolute inset-0 flex items-center justify-center">
            <span className="text-6xl">
              {category === "pachinko" ? "🎰" : "🎲"}
            </span>
          </div>
        )}

        {/* Category badge */}
        <div className="absolute top-2 left-2">
          <span className={`px-2 py-1 text-xs font-bold rounded ${
            category === "pachinko"
              ? "bg-yellow-500 text-black"
              : "bg-purple-500 text-white"
          }`}>
            {category === "pachinko" ? "パチンコ" : "スロット"}
          </span>
        </div>

        {/* Hover overlay */}
        <div className="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
          <span className="px-4 py-2 bg-primary text-primary-foreground font-bold rounded-lg">
            プレイする
          </span>
        </div>
      </div>

      {/* Info */}
      <div className="p-3">
        <h3 className="font-bold text-sm truncate">{name}</h3>
        <p className="text-xs text-muted-foreground mt-1">
          最低 {minPoints} pt〜
        </p>
      </div>
    </Link>
  );
}
