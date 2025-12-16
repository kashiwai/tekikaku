"use client";

import { useEffect, useState } from "react";
import { Sparkles, Wrench, Clock } from "lucide-react";

type Props = {
  title: string;
  description: string;
};
export default function Maintenance({ title, description }: Props) {
  const [mounted, setMounted] = useState(false);
  const [dots, setDots] = useState("");

  useEffect(() => {
    setMounted(true);

    // Animated dots for loading effect
    const interval = setInterval(() => {
      setDots((prev) => (prev.length >= 3 ? "" : prev + "."));
    }, 500);

    return () => clearInterval(interval);
  }, []);

  if (!mounted) return null;

  return (
    <div className="fixed top-0 left-0 w-full h-full z-[9999] bg-background">
      <div className="w-full h-full flex relative overflow-auto custom-scrollbar py-12">
        {/* Animated background elements */}
        <div className="absolute inset-0 overflow-hidden">
          {/* Decorative circles with glow effect */}
          <div className="absolute top-1/4 left-1/4 w-96 h-96 bg-primary/20 rounded-full blur-3xl animate-pulse" />
          <div
            className="absolute bottom-1/4 right-1/4 w-96 h-96 bg-accent/20 rounded-full blur-3xl animate-pulse"
            style={{ animationDelay: "2s" }}
          />

          {/* Floating casino chips */}
          <div className="absolute top-20 left-[10%] w-16 h-16 opacity-10 animate-bounce">
            <div className="w-full h-full rounded-full border-4 border-primary bg-secondary" />
          </div>
          <div
            className="absolute top-40 right-[15%] w-12 h-12 opacity-10 animate-bounce"
            style={{ animationDelay: "1s" }}
          >
            <div className="w-full h-full rounded-full border-4 border-accent bg-secondary" />
          </div>
          <div
            className="absolute bottom-32 left-[20%] w-20 h-20 opacity-10 animate-bounce"
            style={{ animationDelay: "2s" }}
          >
            <div className="w-full h-full rounded-full border-4 border-primary bg-secondary" />
          </div>
          <div
            className="absolute bottom-20 right-[25%] w-14 h-14 opacity-10 animate-bounce"
            style={{ animationDelay: "3s" }}
          >
            <div className="w-full h-full rounded-full border-4 border-accent bg-secondary" />
          </div>
        </div>

        {/* Main content */}
        <div className="relative z-10 w-full m-auto flex flex-col items-center justify-center px-4 sm:px-6 lg:px-8">
          {/* Logo/Icon area */}
          <div className="mb-8 relative">
            <div className="relative">
              {/* Rotating ring */}
              <div className="absolute inset-0 -m-4">
                <div className="w-32 h-32 border-2 border-primary/30 rounded-full animate-spin" />
              </div>

              {/* Center icon */}
              <div className="relative w-24 h-24 bg-primary/15 rounded-full flex items-center justify-center shadow-2xl">
                <Wrench className="w-12 h-12 text-primary" strokeWidth={1.5} />
                <div className="absolute -top-2 -right-2 w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                  <Sparkles className="w-5 h-5 text-foreground" />
                </div>
              </div>
            </div>
          </div>

          {/* Title */}
          <h1 className="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold text-center mb-6 text-balance">
            <span className="bg-clip-text text-transparent bg-gradient-to-r from-primary via-accent to-primary bg-[length:200%_auto] animate-shimmer">
              {title}
            </span>
          </h1>

          {/* Description */}
          <p className="text-base sm:text-lg md:text-xl text-muted-foreground text-center max-w-2xl mb-12 leading-relaxed text-pretty">
            {description}
          </p>

          {/* Status indicator */}
          <div className="flex items-center gap-3 px-6 py-3 bg-background border border-foreground/10 rounded-full shadow-lg">
            <div className="relative">
              <Clock className="w-5 h-5 text-primary" />
              <span className="absolute -top-1 -right-1 w-2 h-2 bg-primary rounded-full animate-ping" />
              <span className="absolute -top-1 -right-1 w-2 h-2 bg-primary rounded-full" />
            </div>
            <span className="text-sm font-medium text-card-foreground">
              Maintenance in progress{dots}
            </span>
          </div>

          {/* Feature cards */}
          <div className="mt-16 grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 w-full max-w-3xl">
            {[
              {
                icon: "🎰",
                title: "Enhanced Games",
                desc: "New slot machines",
              },
              {
                icon: "⚡",
                title: "Faster Performance",
                desc: "Lightning-quick loads",
              },
              { icon: "🎁", title: "Exciting Rewards", desc: "Better bonuses" },
            ].map((feature, index) => (
              <div
                key={index}
                className="bg-background border border-foreground/10 rounded-2xl p-6 text-center hover:border-primary/50 transition-all duration-300 hover:shadow-lg hover:shadow-primary/10"
                style={{ animationDelay: `${index * 0.1}s` }}
              >
                <div className="text-4xl mb-3">{feature.icon}</div>
                <h3 className="font-semibold text-card-foreground mb-1">
                  {feature.title}
                </h3>
                <p className="text-sm text-muted-foreground">{feature.desc}</p>
              </div>
            ))}
          </div>

          {/* Footer text */}
          <p className="mt-12 text-sm text-muted-foreground">
            Expected return time:{" "}
            <span className="font-semibold text-foreground">Soon</span>
          </p>
        </div>
      </div>
    </div>
  );
}
