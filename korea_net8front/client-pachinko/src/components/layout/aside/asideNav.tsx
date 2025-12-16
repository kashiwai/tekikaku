import { ReactNode } from "react";

type Props = {
  title: string;
  children: ReactNode;
};

export default function AsideNav({ title, children }: Props) {
  return (
    <nav className="flex flex-col gap-1 pb-3">
      <h2 className="text-xs font-medium text-secondary">{title}</h2>
      <div className="flex flex-col overflow-hidden">{children}</div>
    </nav>
  );
}
