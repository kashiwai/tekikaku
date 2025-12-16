import IconBase from "@/components/icon/iconBase";
import { AsideNavType } from "@/config/aside.config";
import Link from "next/link";
import { LayoutState } from "@/store/layout.store";

type StoreActionProps = Pick<LayoutState, "isAsideOpen" | "toggleAside">;

type Props = {
  nav: AsideNavType;
  isActive: boolean;
  isMobile: boolean;
  onAction: (action: AsideNavType["action"]) => void;
  /* eslint-disable @typescript-eslint/no-explicit-any */
  t: any;
} & StoreActionProps;

export default function AsideLink({
  nav: { href, icon, label, action },
  isActive = false,
  isAsideOpen,
  toggleAside,
  isMobile,
  onAction,
  t,
}: Props) {
  return (
    <Link
      href={href}
      onClick={(e) => {
        if (action) {
          e.preventDefault();
          onAction(action);
        }

        if (isMobile) {
          toggleAside();
        }
      }}
      prefetch={false}
      className={`${
        isActive
          ? "text-primary"
          : "text-secondary/70 dark:text-secondary hover:text-primary"
      } piano-container active:scale-95 flex items-center gap-3 px-2 h-10 text-sm transition-all`}
    >
      <div className="[&>svg]:size-5">
        <IconBase icon={icon} />
      </div>
      {isAsideOpen && (
        <div
          className={`${
            isActive ? "text-neutral" : ""
          } piano-text text-nowrap mt-1 truncate`}
        >
          {t(label)
            .split("")
            .map((char: string, i: number) => (
              <span key={i} style={{ animationDelay: `${i * 0.05}s` }}>
                {char}
              </span>
            ))}
        </div>
      )}
    </Link>
  );
}
