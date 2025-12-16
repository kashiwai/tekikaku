import { Net8ModelCard } from "@/components/net8/Net8ModelCard";

// デモ用の機種リスト（実際はAPIから取得）
const DEMO_MODELS = [
  { id: "HOKUTO4GO", name: "北斗の拳4", category: "pachinko" as const, minPoints: 100 },
  { id: "ZENIGATA01", name: "銭形平次", category: "pachinko" as const, minPoints: 100 },
  { id: "REINYAN01", name: "麗-花萌ゆる8人の皇子たち-", category: "pachinko" as const, minPoints: 100 },
  { id: "EVANGELION", name: "エヴァンゲリオン", category: "pachinko" as const, minPoints: 150 },
  { id: "JUGGLER01", name: "ジャグラー", category: "slot" as const, minPoints: 50 },
  { id: "OSHIDOMEI", name: "押忍!番長", category: "slot" as const, minPoints: 100 },
];

export default function PachinkoPage() {
  return (
    <div className="container mx-auto py-6 space-y-6">
      {/* Header */}
      <div className="text-center mb-8">
        <h1 className="text-3xl font-bold bg-gradient-to-r from-yellow-400 to-orange-500 bg-clip-text text-transparent">
          NET8 パチンコ・スロット
        </h1>
        <p className="text-muted-foreground mt-2">
          実機をリモートでプレイ - NET8 SDK連携
        </p>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-3 gap-4 max-w-2xl mx-auto">
        <div className="p-4 bg-card rounded-lg border text-center">
          <p className="text-2xl font-bold text-yellow-500">6</p>
          <p className="text-xs text-muted-foreground">機種数</p>
        </div>
        <div className="p-4 bg-card rounded-lg border text-center">
          <p className="text-2xl font-bold text-green-500">Demo</p>
          <p className="text-xs text-muted-foreground">環境</p>
        </div>
        <div className="p-4 bg-card rounded-lg border text-center">
          <p className="text-2xl font-bold text-blue-500">SDK v1.1</p>
          <p className="text-xs text-muted-foreground">バージョン</p>
        </div>
      </div>

      {/* Pachinko Section */}
      <section>
        <h2 className="text-xl font-bold mb-4 flex items-center gap-2">
          <span className="text-2xl">🎰</span> パチンコ
        </h2>
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          {DEMO_MODELS.filter((m) => m.category === "pachinko").map((model) => (
            <Net8ModelCard key={model.id} {...model} />
          ))}
        </div>
      </section>

      {/* Slot Section */}
      <section>
        <h2 className="text-xl font-bold mb-4 flex items-center gap-2">
          <span className="text-2xl">🎲</span> スロット
        </h2>
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          {DEMO_MODELS.filter((m) => m.category === "slot").map((model) => (
            <Net8ModelCard key={model.id} {...model} />
          ))}
        </div>
      </section>

      {/* Info */}
      <div className="mt-8 p-4 bg-blue-500/10 border border-blue-500/30 rounded-lg">
        <h3 className="font-bold text-blue-400 mb-2">NET8 SDK について</h3>
        <ul className="text-sm text-muted-foreground space-y-1">
          <li>• 実機パチンコ・スロットをリモートでプレイ</li>
          <li>• WebRTCによるリアルタイム映像配信</li>
          <li>• ポイント管理・トランザクション記録</li>
          <li>• デモ環境では仮想機器が割り当てられます</li>
        </ul>
      </div>
    </div>
  );
}
