import React, { useState, useEffect } from 'react';
import { NavLink, Link, useLocation } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import { Menu, X, Cpu, Globe, Zap, ArrowUpRight } from 'lucide-react';
import { ROUTE_PATHS, NAV_ITEMS, getYear } from '@/lib/index';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';
interface LayoutProps {
  children: React.ReactNode;
}
export function Layout({
  children
}: LayoutProps) {
  const [isScrolled, setIsScrolled] = useState(false);
  const {
    pathname
  } = useLocation();
  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 20);
    };
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  // Ensure scroll to top on route change
  useEffect(() => {
    window.scrollTo(0, 0);
  }, [pathname]);
  return <div className="relative min-h-screen bg-background text-foreground selection:bg-primary/30 selection:text-primary">
      {/* Grid Overlay for Cyber-Brutalism Look */}
      <div className="pointer-events-none fixed inset-0 z-[-1] opacity-[0.03] bg-[linear-gradient(to_right,#808080_1px,transparent_1px),linear-gradient(to_bottom,#808080_1px,transparent_1px)] bg-[size:40px_40px]" />
      
      {/* Navigation Header */}
      <header className={`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ${isScrolled ? 'h-16 bg-background/80 backdrop-blur-xl border-b border-border/50 shadow-2xl' : 'h-24 bg-transparent'}`}>
        <div className="container mx-auto h-full px-6 flex items-center justify-between">
          <Link to={ROUTE_PATHS.HOME} className="flex items-center gap-2 group">
            <div className="relative w-10 h-10 bg-primary flex items-center justify-center rounded-sm overflow-hidden">
              <Cpu className="w-6 h-6 text-primary-foreground group-hover:scale-110 transition-transform" />
              <div className="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300" />
            </div>
            <span className="text-2xl font-black tracking-tighter uppercase font-sans">
              Restill<span className="text-primary">.AI</span>
            </span>
          </Link>

          {/* Desktop Nav */}
          <nav className="hidden md:flex items-center gap-12">
            {NAV_ITEMS.map(item => <NavLink key={item.path} to={item.path} className={({
            isActive
          }) => `
                  relative text-sm font-bold uppercase tracking-widest transition-colors hover:text-primary
                  ${isActive ? 'text-primary' : 'text-foreground/70'}
                `}>
                {({
              isActive
            }) => <>
                    {item.label}
                    {isActive && <motion.div layoutId="nav-underline" className="absolute -bottom-1 left-0 w-full h-0.5 bg-primary" />}
                  </>}
              </NavLink>)}
            <Button variant="outline" className="border-primary/50 text-primary hover:bg-primary hover:text-primary-foreground font-mono text-xs">
              ESTABLISH CONNECTION
            </Button>
          </nav>

          {/* Mobile Nav */}
          <div className="md:hidden flex items-center gap-4">
            <Sheet>
              <SheetTrigger asChild>
                <Button variant="ghost" size="icon" className="hover:bg-primary/10">
                  <Menu className="w-6 h-6" />
                </Button>
              </SheetTrigger>
              <SheetContent side="right" className="bg-background/95 backdrop-blur-2xl border-l border-primary/20">
                <div className="flex flex-col gap-8 mt-12">
                  {NAV_ITEMS.map(item => <NavLink key={item.path} to={item.path} className={({
                  isActive
                }) => `
                        text-3xl font-black uppercase tracking-tighter transition-colors
                        ${isActive ? 'text-primary' : 'text-foreground/40 hover:text-foreground'}
                      `}>
                      {item.label}
                    </NavLink>)}
                  <div className="pt-8 border-t border-border">
                    <p className="text-xs font-mono text-muted-foreground mb-4 uppercase">Global Network</p>
                    <p className="text-sm leading-relaxed">
                      Worldwide AI Operations<br />
                      24/7 Neural Network Active
                    </p>
                  </div>
                </div>
              </SheetContent>
            </Sheet>
          </div>
        </div>
      </header>

      <main className="relative">
        {children}
      </main>

      {/* Footer Section */}
      <footer className="bg-card mt-24 border-t border-border py-24 relative overflow-hidden">
        <div className="absolute top-0 right-0 w-96 h-96 bg-primary/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2" />
        <div className="container mx-auto px-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-16">
            <div className="md:col-span-2">
              <Link to={ROUTE_PATHS.HOME} className="inline-flex items-center gap-2 mb-8">
                <Zap className="w-8 h-8 text-primary fill-primary" />
                <span className="text-3xl font-black uppercase tracking-tighter">RESTILL</span>
              </Link>
              <h3 className="text-4xl md:text-5xl font-black leading-none mb-8 tracking-tight">
                AI ORCHESTRATION <br />
                <span className="text-muted-foreground">FOR THE NEW ERA.</span>
              </h3>
              <p className="text-muted-foreground max-w-md font-medium">
                Empowering the world's leading brands with original RESTILL-AI agents. 
                We bridge the gap between human intuition and machine intelligence through advanced LLM orchestration.
              </p>
            </div>

            <div>
              <h4 className="font-mono text-xs text-primary uppercase tracking-[0.2em] mb-8">Structure</h4>
              <ul className="space-y-4">
                {NAV_ITEMS.map(item => <li key={item.path}>
                    <Link to={item.path} className="group flex items-center gap-2 text-lg font-bold uppercase hover:text-primary transition-colors">
                      {item.label}
                      <ArrowUpRight className="w-4 h-4 opacity-0 group-hover:opacity-100 -translate-y-1 transition-all" />
                    </Link>
                  </li>)}
              </ul>
            </div>

            <div>
              <h4 className="font-mono text-xs text-primary uppercase tracking-[0.2em] mb-8">Contact Base</h4>
              <address className="not-italic font-medium text-muted-foreground space-y-4">
                <p className="text-foreground">GLOBAL OPERATIONS</p>
                
                <div className="flex gap-4 pt-4">
                  <a href="#" className="p-2 bg-secondary rounded-sm hover:bg-primary hover:text-primary-foreground transition-colors">
                    <Globe className="w-5 h-5" />
                  </a>
                </div>
              </address>
            </div>
          </div>

          <div className="mt-24 pt-8 border-t border-border flex flex-col md:flex-row justify-between items-center gap-8">
            <div className="flex gap-8 text-xs font-mono text-muted-foreground">
              <span>CORE_v4.2.0</span>
              <span>LATENCY: 14ms</span>
              <span>AGENTS_ACTIVE: 320</span>
            </div>
            <p className="text-xs font-mono uppercase tracking-widest text-muted-foreground">
              © {getYear()} RESTILL Inc. All Rights Reserved. Engineered for Transcendence.
            </p>
          </div>
        </div>
      </footer>

      {/* Global Scroll Indicator (Minimalist) */}
      <div className="fixed bottom-8 left-8 z-50 hidden xl:block">
        <div className="h-12 w-[2px] bg-border relative overflow-hidden">
          <motion.div className="absolute top-0 left-0 w-full bg-primary" animate={{
          height: ['0%', '100%']
        }} transition={{
          duration: 2,
          repeat: Infinity,
          ease: "easeInOut"
        }} />
        </div>
      </div>
    </div>;
}