import React from 'react';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { ArrowRight, BrainCircuit, Cpu, ShieldAlert, Zap } from 'lucide-react';
import { IMAGES } from '@/assets/images';
import { ROUTE_PATHS } from '@/lib/index';
import { ServiceCard, ClientCard, StatCard } from '@/components/Cards';
import { services, clients, stats } from '@/data/index';
import { springPresets, fadeInUp, staggerContainer, staggerItem } from '@/lib/motion';

export default function Home() {
  return (
    <div className="relative min-h-screen bg-background overflow-hidden">
      {/* Hero Section */}
      <section className="relative h-screen flex items-center justify-center pt-20">
        <div className="absolute inset-0 z-0">
          <img 
            src={IMAGES.AI_NEURAL_2} 
            alt="Neural Network Background" 
            className="w-full h-full object-cover opacity-30 grayscale contrast-125"
          />
          <div className="absolute inset-0 bg-gradient-to-b from-background/50 via-transparent to-background" />
        </div>

        <div className="container mx-auto px-4 z-10">
          <motion.div 
            initial={{ opacity: 0, y: 40 }}
            animate={{ opacity: 1, y: 0 }}
            transition={springPresets.gentle}
            className="max-w-5xl mx-auto text-center"
          >
            <h1 className="text-6xl md:text-8xl lg:text-9xl font-extrabold tracking-tighter leading-none mb-8">
              RESTILL-AI <br />
              <span className="text-primary">ARCHITECTS</span>
            </h1>
            <p className="text-xl md:text-2xl font-mono text-muted-foreground mb-12 max-w-3xl mx-auto leading-relaxed">
              世界から誰もいなくなっても、RESTILL-AIがすべてを解決。
              <br />
              <span className="text-foreground/80">
                Even if humanity fades, the architecture of intelligence remains. We deploy autonomous agents to orchestrate the future of industry.
              </span>
            </p>
            <div className="flex flex-col sm:flex-row gap-6 justify-center">
              <Link 
                to={ROUTE_PATHS.ABOUT}
                className="bg-primary text-primary-foreground px-10 py-5 rounded-none font-bold text-lg flex items-center justify-center gap-2 hover:bg-primary/90 transition-all active:scale-95 border border-primary"
              >
                INITIATE PROTOCOL <ArrowRight className="w-5 h-5" />
              </Link>
              <Link 
                to={ROUTE_PATHS.CONTACT}
                className="bg-transparent text-foreground px-10 py-5 rounded-none font-bold text-lg border border-foreground/20 hover:bg-foreground/5 transition-all"
              >
                VIEW ECOSYSTEM
              </Link>
            </div>
          </motion.div>
        </div>

        {/* Scrolling Stats Bar */}
        <div className="absolute bottom-0 w-full bg-primary/5 backdrop-blur-md border-t border-primary/20 py-8">
          <div className="container mx-auto px-4">
            <motion.div 
              variants={staggerContainer}
              initial="hidden"
              whileInView="show"
              viewport={{ once: true }}
              className="grid grid-cols-1 md:grid-cols-3 gap-8"
            >
              {stats.map((stat) => (
                <motion.div key={stat.label} variants={staggerItem}>
                  <StatCard 
                    label={stat.label} 
                    value={stat.value} 
                    description={stat.description} 
                  />
                </motion.div>
              ))}
            </motion.div>
          </div>
        </div>
      </section>

      {/* Orchestration Section */}
      <section className="py-32 relative">
        <div className="container mx-auto px-4">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-24 items-center">
            <motion.div 
              initial={{ opacity: 0, x: -50 }}
              whileInView={{ opacity: 1, x: 0 }}
              transition={springPresets.gentle}
              viewport={{ once: true }}
            >
              <h2 className="text-5xl font-extrabold mb-8 tracking-tight">
                MULTI-LLM <br />
                <span className="text-accent">ORCHESTRATION</span>
              </h2>
              <div className="space-y-6 text-lg text-muted-foreground">
                <p>
                  Our proprietary <span className="text-foreground font-bold">RESTILL-AI</span> engine is built upon the synthesis of GPT-4, Claude 3.5, and Gemini Pro.
                </p>
                <p>
                  We don't just provide chatbots. We provide <span className="text-foreground">AI Consultant Agents</span> specialized in the high-precision management structures preferred by Japanese business leaders.
                </p>
                <p>
                  Through <span className="text-foreground">ClaudeCode</span> integration and autonomous development orchestration, we automate 90% of the project lifecycle, allowing a team of 10 to outperform global agencies.
                </p>
              </div>
              <div className="mt-10 grid grid-cols-2 gap-4">
                {['GPT-4o', 'CLAUDE 3.5', 'GEMINI 1.5', 'RESTILL-CORE'].map((tech) => (
                  <div key={tech} className="flex items-center gap-3 p-4 border border-border bg-card">
                    <Zap className="w-5 h-5 text-primary" />
                    <span className="font-mono text-sm font-bold">{tech}</span>
                  </div>
                ))}
              </div>
            </motion.div>

            <motion.div 
              initial={{ opacity: 0, scale: 0.9 }}
              whileInView={{ opacity: 1, scale: 1 }}
              transition={springPresets.gentle}
              viewport={{ once: true }}
              className="relative aspect-square"
            >
              <div className="absolute inset-0 border-[20px] border-primary/10 -rotate-3 translate-x-4 translate-y-4" />
              <img 
                src={IMAGES.CYBER_TECH_3} 
                alt="AI Orchestration Visualization" 
                className="relative z-10 w-full h-full object-cover grayscale brightness-75 hover:grayscale-0 transition-all duration-700"
              />
              <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-20">
                <div className="w-32 h-32 bg-primary/20 backdrop-blur-xl flex items-center justify-center border border-primary animate-pulse">
                  <Cpu className="w-12 h-12 text-primary" />
                </div>
              </div>
            </motion.div>
          </div>
        </div>
      </section>

      {/* Services Grid */}
      <section className="py-32 bg-secondary/30">
        <div className="container mx-auto px-4">
          <div className="text-center mb-24">
            <span className="text-primary font-mono font-bold tracking-[0.5em] block mb-4">SOLUTIONS</span>
            <h2 className="text-5xl md:text-6xl font-extrabold">UNCONVENTIONAL LOGIC</h2>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {services.map((service) => (
              <ServiceCard 
                key={service.id}
                title={service.title}
                description={service.description}
                icon={
                  service.iconName === 'BrainCircuit' ? <BrainCircuit className="w-8 h-8" /> : 
                  service.iconName === 'Cpu' ? <Cpu className="w-8 h-8" /> : 
                  <ShieldAlert className="w-8 h-8" />
                }
              />
            ))}
          </div>
        </div>
      </section>

      {/* Client Showcase */}
      <section className="py-32 border-y border-border">
        <div className="container mx-auto px-4">
          <div className="flex flex-col md:flex-row justify-between items-end mb-16 gap-8">
            <div>
              <h2 className="text-4xl font-extrabold mb-4">TRUSTED BY THE GIANTS</h2>
              <p className="text-muted-foreground font-mono">ESTABLISHING DIGITAL SOVEREIGNTY FOR GLOBAL LEADERS.</p>
            </div>
            <div className="text-right">
              <span className="text-7xl font-black text-primary/10">08 CLIENTS</span>
            </div>
          </div>

          <div className="grid grid-cols-2 md:grid-cols-4 gap-px bg-border border border-border">
            {clients.map((client) => (
              <div key={client.id} className="bg-background">
                <ClientCard name={client.name} />
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Bottom CTA Section */}
      <section className="relative py-40 overflow-hidden">
        <div className="absolute inset-0 z-0">
          <img 
            src={IMAGES.DATA_VIZ_1} 
            alt="Data Visualization" 
            className="w-full h-full object-cover opacity-10"
          />
        </div>
        <div className="container mx-auto px-4 relative z-10 text-center">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={springPresets.gentle}
          >
            <h2 className="text-6xl md:text-8xl font-black mb-12 tracking-tighter">
              JOIN THE <br />
              <span className="text-primary">RESTILL ERA</span>
            </h2>
            <p className="text-xl text-muted-foreground mb-16 max-w-2xl mx-auto">
              The transition to autonomous business models is no longer optional. 
              Secure your position in the post-human operational landscape.
            </p>
            <Link 
              to={ROUTE_PATHS.CONTACT}
              className="bg-foreground text-background px-16 py-6 rounded-none font-black text-xl hover:bg-primary hover:text-primary-foreground transition-all active:scale-95"
            >
              INITIALIZE CONSULTATION
            </Link>
          </motion.div>
        </div>
      </section>

      {/* Decorative Elements */}
      <div className="fixed top-0 left-0 w-1 h-full bg-gradient-to-b from-primary via-accent to-transparent opacity-20 z-50" />
      <div className="fixed bottom-10 right-10 z-50 pointer-events-none">
        <div className="bg-background/80 backdrop-blur-md border border-border p-4 font-mono text-[10px] space-y-1">
          <p className="text-primary">[SYSTEM STATUS: OPERATIONAL]</p>
          <p>Uptime: 99.999%</p>
          <p>Agents: 320/320 Active</p>
          <p>Location: SHINJUKU-KU, TOKYO</p>
        </div>
      </div>
    </div>
  );
}