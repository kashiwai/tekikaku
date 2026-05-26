import Link from "next/link";

export default function PoolPage() {
  return (
    <div className="bg-white rounded-[6px] border border-[#E5E8EC] p-5 shadow-sm">
      <h1 className="text-sm font-semibold text-gray-900 mb-4">流動性</h1>
      <div className="flex gap-3">
        <Link
          href="/pool/add"
          className="flex-1 py-3 text-center text-sm font-medium bg-[#2563EB] text-white rounded-[6px] hover:bg-[#1D4ED8] transition-colors"
        >
          LP 追加
        </Link>
        <Link
          href="/pool/remove"
          className="flex-1 py-3 text-center text-sm font-medium border border-[#2563EB] text-[#2563EB] rounded-[6px] hover:bg-[#EFF6FF] transition-colors"
        >
          LP 削除
        </Link>
      </div>
    </div>
  );
}
