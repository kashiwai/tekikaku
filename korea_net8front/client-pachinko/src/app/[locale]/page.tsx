import CategoryBanners from "@/components/banners/CategoryBanners";
import HeroBanner from "@/components/banners/HeroBanner";
import CasinoList from "@/components/sections/casinoList";
import SlotList from "@/components/sections/slotList";
import RecentBetsTable from "@/components/sections/recentBetsTable";
import ProvidersMarque from "@/components/sections/providersMarque";

type Props = {
  searchParams: Promise<URLSearchParams>;
};

export default async function Home({ searchParams }: Props) {
  const queryParams = await searchParams;
    
  // ローカルデータのみ使用（API呼び出しなし）
  const banners: { game: never[]; logout: never[]; category: never[] } = { game: [], logout: [], category: [] };
  const promotions: never[] = [];

  return (
    <>
      <HeroBanner
        banners={banners.logout}
        promotions={promotions}
        searchParams={queryParams}
      />
      <CategoryBanners banners={banners.category} />
      <CasinoList />
      <SlotList />
      <RecentBetsTable />
      <ProvidersMarque />
    </>
  );
}
