import { Button } from "@/components/ui/button";
import { ROUTES } from "@/config/routes.config";
import { Link } from "@/i18n/navigation";
import { Dices, Home, TrendingUp } from "lucide-react";

export default function Page() {
  return (
    <div className="fixed top-0 left-0 w-full h-full z-[9999]">
      <div className="h-full w-full bg-background relative overflow-auto">
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
            <div className="w-full h-full rounded-full border-4 border-primary bg-foreground/5" />
          </div>
          <div
            className="absolute top-40 right-[15%] w-12 h-12 opacity-10 animate-bounce"
            style={{ animationDelay: "1s" }}
          >
            <div className="w-full h-full rounded-full border-4 border-accent bg-foreground/5" />
          </div>
          <div
            className="absolute bottom-32 left-[20%] w-20 h-20 opacity-10 animate-bounce"
            style={{ animationDelay: "2s" }}
          >
            <div className="w-full h-full rounded-full border-4 border-primary bg-foreground/5" />
          </div>
          <div
            className="absolute bottom-20 right-[25%] w-14 h-14 opacity-10 animate-bounce"
            style={{ animationDelay: "3s" }}
          >
            <div className="w-full h-full rounded-full border-4 border-accent bg-foreground/5" />
          </div>
        </div>

        {/* Main content */}
        <div className="relative z-10 w-full min-h-screen flex flex-col items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
          {/* 404 Number with dice icon */}
          <div className="mb-8 relative">
            <div className="flex items-center gap-4">
              <span className="text-8xl sm:text-9xl md:text-[12rem] font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary via-accent to-primary bg-[length:200%_auto] animate-shimmer">
                4
              </span>
              <div className="relative mx-4">
                {/* Rotating ring */}
                <div className="absolute inset-0 -m-4">
                  <div className="w-24 h-24 sm:w-32 sm:h-32 border-2 border-primary/30 rounded-lg animate-spin-slow" />
                </div>

                {/* Center dice icon */}
                <div className="relative w-16 h-16 sm:w-24 sm:h-24 bg-foreground/5 rounded-xl flex items-center justify-center shadow-2xl">
                  <Dices
                    className="w-8 h-8 sm:w-12 sm:h-12 text-primary"
                    strokeWidth={1.5}
                  />
                </div>
              </div>
              <span className="text-8xl sm:text-9xl md:text-[12rem] font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary via-accent to-primary bg-[length:200%_auto] animate-shimmer">
                4
              </span>
            </div>
          </div>

          {/* Title */}
          <h1 className="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold text-center mb-4 text-balance">
            <span className="text-foreground">Oops! Wrong Bet</span>
          </h1>

          {/* Description */}
          <p className="text-base sm:text-lg md:text-xl text-muted-foreground text-center max-w-2xl mb-12 leading-relaxed text-pretty">
            Looks like this page hit the jackpot... of not existing. The page
            you're looking for might have been moved, deleted, or never existed
            in the first place.
          </p>

          {/* Action buttons */}
          <div className="flex flex-col sm:flex-row gap-4 mb-16">
            <Link href="/">
              <Button size="default" color="primary">
                <Home className="w-5 h-5" />
                Back to Home
              </Button>
            </Link>
          </div>

          {/* Popular links */}
          <div className="w-full max-w-3xl">
            <h2 className="text-xl font-semibold text-center mb-6 text-foreground">
              Try Your Luck Here
            </h2>
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
              {[
                {
                  icon: Dices,
                  title: "Casino Games",
                  desc: "Explore our games",
                  href: ROUTES.CASINO,
                },
                {
                  icon: TrendingUp,
                  title: "Promotions",
                  desc: "Latest bonuses",
                  href: ROUTES.PROMOTIONS,
                },
                {
                  icon: Home,
                  title: "Live Casino",
                  desc: "Play with dealers",
                  href: ROUTES.CASINO,
                },
              ].map((link, index) => (
                <Link
                  key={index}
                  href={link.href}
                  className="bg-background border border-foreground/10 rounded-xl p-6 text-center hover:border-primary/50 transition-all duration-300 hover:shadow-lg hover:shadow-primary/10 group"
                  style={{ animationDelay: `${index * 0.1}s` }}
                >
                  <div className="w-12 h-12 mx-auto mb-4 bg-foreground/5 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                    <link.icon className="w-6 h-6 text-primary" />
                  </div>
                  <h3 className="font-semibold text-card-foreground mb-1">
                    {link.title}
                  </h3>
                  <p className="text-sm text-muted-foreground">{link.desc}</p>
                </Link>
              ))}
            </div>
          </div>

          {/* Footer text */}
          <p className="mt-12 text-sm text-muted-foreground text-center">
            Error Code:{" "}
            <span className="font-mono font-semibold text-foreground">404</span>{" "}
            - Page Not Found
          </p>
        </div>
      </div>
    </div>
  );
}
