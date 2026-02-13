import React from 'react';
import { motion } from 'framer-motion';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Utility to merge tailwind classes safely
 */
function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

/**
 * ServiceCard component for showcasing RESTILL-AI's core offerings.
 * Features a cyber-brutalism design with glassmorphism effects and sharp accents.
 */
export function ServiceCard({ 
  title, 
  description, 
  icon 
}: { 
  title: string; 
  description: string; 
  icon: React.ReactNode 
}) {
  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true }}
      whileHover={{ y: -8, transition: { duration: 0.2 } }}
      className={cn(
        "group relative h-full overflow-hidden border border-border bg-card/40 p-10 backdrop-blur-md transition-all",
        "hover:bg-card/60 hover:border-primary/50"
      )}
    >
      {/* Background Neural Glow Effect */}
      <div className="absolute -top-24 -right-24 h-64 w-64 rounded-full bg-primary/5 blur-[100px] transition-all group-hover:bg-primary/20" />
      
      <div className="relative z-10">
        {/* Icon Container with Pulsing Border */}
        <div className="mb-10 inline-flex items-center justify-center text-primary">
          <div className="relative">
            <div className="absolute inset-0 scale-150 bg-primary/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity" />
            <div className="relative h-12 w-12 flex items-center justify-center">
              {React.cloneElement(icon as React.ReactElement, { size: 40, strokeWidth: 1.5 })}
            </div>
          </div>
        </div>
        
        {/* Cyber Typography */}
        <h3 className="mb-5 font-sans text-3xl font-black leading-none tracking-tight text-foreground uppercase">
          {title}
        </h3>
        
        <p className="font-sans text-lg leading-relaxed text-muted-foreground/90">
          {description}
        </p>
        
        {/* Technical Footer Decoration */}
        <div className="mt-10 flex items-center gap-4">
          <div className="h-px flex-1 bg-border group-hover:bg-primary/30 transition-colors" />
          <span className="font-mono text-[10px] font-bold tracking-[0.4em] text-muted-foreground group-hover:text-primary transition-colors">
            PROTOCOL.01
          </span>
        </div>
      </div>
      
      {/* Brutalist Corner Accents */}
      <div className="absolute top-0 left-0 w-4 h-4 border-t border-l border-primary/40" />
      <div className="absolute bottom-0 right-0 w-4 h-4 border-b border-r border-primary/40" />
    </motion.div>
  );
}

/**
 * ClientCard component for displaying high-profile partner logos.
 * Minimalist industrial design with grayscale hover states.
 */
export function ClientCard({ 
  name, 
  logo 
}: { 
  name: string; 
  logo?: string 
}) {
  return (
    <div className={cn(
      "group relative flex h-32 items-center justify-center overflow-hidden border border-border bg-card/10 transition-all",
      "hover:bg-card/30 hover:border-primary/20 hover:shadow-[0_0_30px_-10px_rgba(var(--primary),0.1)]"
    )}>
      <div className="relative z-10 px-8 transition-all duration-500 grayscale group-hover:grayscale-0 group-hover:scale-105">
        {logo ? (
          <img src={logo} alt={name} className="h-10 w-auto object-contain" />
        ) : (
          <span className="font-mono text-base font-black tracking-[0.5em] text-muted-foreground/60 transition-colors group-hover:text-foreground">
            {name}
          </span>
        )}
      </div>
      
      {/* Scanline Effect on Hover */}
      <div className="pointer-events-none absolute inset-0 opacity-0 group-hover:opacity-10 transition-opacity bg-[linear-gradient(transparent_0%,rgba(255,255,255,0.05)_50%,transparent_100%)] bg-[length:100%_4px]" />
      
      {/* Accent Line */}
      <div className="absolute bottom-0 left-1/2 h-[2px] w-0 -translate-x-1/2 bg-primary transition-all duration-500 group-hover:w-full" />
    </div>
  );
}

/**
 * StatCard component for displaying key company metrics.
 * Focuses on high-contrast technical typography and vertical emphasis.
 */
export function StatCard({ 
  label, 
  value, 
  description 
}: { 
  label: string; 
  value: string; 
  description: string 
}) {
  return (
    <div className="group relative border-l-2 border-border py-10 pl-10 transition-all hover:border-primary">
      {/* Moving Indicator on Left Border */}
      <div className="absolute -left-[2px] top-0 h-0 w-[2px] bg-primary transition-all duration-700 ease-out group-hover:h-full" />
      
      <div className="relative z-10">
        {/* Label Header */}
        <div className="mb-2 flex items-center gap-3">
          <span className="h-[2px] w-4 bg-primary" />
          <span className="font-mono text-[11px] font-bold tracking-[0.3em] text-primary uppercase">
            {label}
          </span>
        </div>
        
        {/* Large Value Display */}
        <div className="flex items-baseline gap-2">
          <motion.span 
            initial={{ opacity: 0, x: -10 }}
            whileInView={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.5, delay: 0.1 }}
            className="font-mono text-7xl font-black tracking-tighter text-foreground"
          >
            {value}
          </motion.span>
          <span className="text-2xl font-black text-primary">/</span>
        </div>
        
        {/* Description Text */}
        <p className="mt-6 max-w-[240px] font-sans text-base leading-relaxed text-muted-foreground group-hover:text-foreground transition-colors">
          {description}
        </p>
      </div>
      
      {/* Subtle Background Interaction */}
      <div className="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-r from-primary/5 to-transparent opacity-0 transition-opacity group-hover:opacity-100" />
    </div>
  );
}
