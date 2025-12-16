"use client";
import { ReactNode } from "react";

type Props = {
  children: ReactNode;
};

export default function Main({ children }: Props) {
  return (
    <main className="container--main flex flex-col gap-6 md:gap-12 py-6">
      {children}
    </main>
  );
}
