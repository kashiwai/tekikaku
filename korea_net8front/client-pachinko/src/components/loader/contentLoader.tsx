import { cn } from "@/lib/utils";

export default function ContentLoader({ className }: { className?: string }) {
  return (
    <div className={cn("flex w-full", className)}>
      <div className="w-[50px] h-[50px] rounded-full border-4 border-neutral/10 border-t-neutral m-auto will-change-transform animate-spin" />
    </div>
  );
}
