export function TokenTag({ token }: { token: "xEGM" | "USDT" }) {
  const styles = {
    xEGM: "bg-[#EFF6FF] text-[#2563EB]",
    USDT: "bg-[#F0FDF4] text-[#16A34A]",
  };
  return (
    <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold ${styles[token]}`}>
      {token}
    </span>
  );
}
