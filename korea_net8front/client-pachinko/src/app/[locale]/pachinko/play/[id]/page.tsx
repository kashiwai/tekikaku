import { Net8GamePlayerIframe } from "@/components/net8/Net8GamePlayerIframe";
import Link from "next/link";
import { cookies } from 'next/headers';
import { mockKoreaDB } from '@/lib/mock/korea-db';

// デモ用機種マスター
const MODELS: Record<string, { name: string; category: "pachinko" | "slot" }> = {
  HOKUTO4GO: { name: "北斗の拳4", category: "pachinko" },
  ZENIGATA01: { name: "銭形平次", category: "pachinko" },
  REINYAN01: { name: "麗-花萌ゆる8人の皇子たち-", category: "pachinko" },
  EVANGELION: { name: "エヴァンゲリオン", category: "pachinko" },
  JUGGLER01: { name: "ジャグラー", category: "slot" },
  OSHIDOMEI: { name: "押忍!番長", category: "slot" },
};

type Props = {
  params: Promise<{
    id: string;
  }>;
};

export default async function PachinkoPlayPage({ params }: Props) {
  const { id } = await params;
  const modelId = decodeURIComponent(id);
  const model = MODELS[modelId];

  if (!model) {
    return (
      <div className="container mx-auto py-12 text-center">
        <h1 className="text-2xl font-bold text-red-500 mb-4">機種が見つかりません</h1>
        <p className="text-muted-foreground mb-6">機種ID: {modelId}</p>
        <Link
          href="/pachinko"
          className="px-4 py-2 bg-primary text-primary-foreground rounded-lg"
        >
          機種一覧に戻る
        </Link>
      </div>
    );
  }

  // ユーザーIDを認証セッションから取得
  const cookieStore = await cookies();
  const sessionCookie = cookieStore.get('user.sid'); // 正しいCookie名を使用
  let userId = "demo_user_001"; // フォールバック
  let net8UserId = "kr_net8_demo_user_001"; // NET8 UserIDフォールバック

  if (sessionCookie?.value) {
    // mockKoreaDBから直接セッション情報を取得
    const session = mockKoreaDB.getSessionById(sessionCookie.value);

    if (session && session.user) {
      userId = session.user.id || session.user.loginId;

      // NET8 UserIDを取得（連携されていなければ自動連携）
      if (session.user.net8UserId) {
        net8UserId = session.user.net8UserId;
      } else {
        const linkResult = mockKoreaDB.linkNet8User(session.user.id);
        if (linkResult.success) {
          net8UserId = linkResult.net8UserId;
        }
      }

      console.log('[Game Page] User authenticated:', {
        koreaUserId: userId,
        net8UserId
      });
    }
  }

  return (
    <div className="container mx-auto py-6">
      {/* Breadcrumb */}
      <nav className="mb-4 text-sm">
        <Link href="/" className="text-muted-foreground hover:text-foreground">
          ホーム
        </Link>
        <span className="mx-2 text-muted-foreground">/</span>
        <Link href="/pachinko" className="text-muted-foreground hover:text-foreground">
          NET8
        </Link>
        <span className="mx-2 text-muted-foreground">/</span>
        <span className="text-foreground">{model.name}</span>
      </nav>

      {/* Game Player - 既存のNET8プレイヤーを使用 */}
      <Net8GamePlayerIframe
        userId={userId}
        net8UserId={net8UserId}
        modelId={modelId}
        modelName={model.name}
      />

      {/* Back link */}
      <div className="mt-6 text-center">
        <Link
          href="/pachinko"
          className="text-muted-foreground hover:text-foreground underline"
        >
          ← 機種一覧に戻る
        </Link>
      </div>
    </div>
  );
}
