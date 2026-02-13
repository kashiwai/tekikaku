import { Toaster } from "@/components/ui/toaster";
import { Toaster as Sonner } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { HashRouter, Routes, Route } from "react-router-dom";
import { ROUTE_PATHS } from "@/lib/index";
import { Layout } from "@/components/Layout";
import Home from "@/pages/Home";
import About from "@/pages/About";
import Contact from "@/pages/Contact";

/**
 * RESTILL-AI App Root
 * Orchestrating the future of AI consultation and development.
 * © 2026 RESTILL Inc.
 */

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5 minutes
      retry: 1,
    },
  },
});

const App = () => {
  return (
    <QueryClientProvider client={queryClient}>
      <TooltipProvider delayDuration={200}>
        <div className="min-h-screen bg-background font-sans selection:bg-primary/30 selection:text-primary">
          <Toaster />
          <Sonner 
            position="bottom-right" 
            theme="dark" 
            closeButton 
            richColors 
          />
          
          <HashRouter>
            <Layout>
              <Routes>
                {/* Main Intelligence Gateway */}
                <Route 
                  path={ROUTE_PATHS.HOME} 
                  element={<Home />} 
                />
                
                {/* AI Orchestration Details */}
                <Route 
                  path={ROUTE_PATHS.ABOUT} 
                  element={<About />} 
                />
                
                {/* Human-AI Interface Portal */}
                <Route 
                  path={ROUTE_PATHS.CONTACT} 
                  element={<Contact />} 
                />
                
                {/* Fallback Redirection to Home */}
                <Route 
                  path="*" 
                  element={<Home />} 
                />
              </Routes>
            </Layout>
          </HashRouter>
        </div>
      </TooltipProvider>
    </QueryClientProvider>
  );
};

export default App;