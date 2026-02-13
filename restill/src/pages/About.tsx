import React from "react";
import { motion } from "framer-motion";
import { Cpu, Zap, Network, BrainCircuit, ShieldCheck, Globe } from "lucide-react";
import { IMAGES } from "@/assets/images";
import { stats, team } from "@/data/index";
import { StatCard } from "@/components/Cards";
import { springPresets, fadeInUp, staggerContainer, staggerItem } from "@/lib/motion";

const About = () => {
  return (
    <div className="min-h-screen bg-background overflow-hidden">
      {/* Hero Section - The Mission */}
      <section className="relative pt-32 pb-20 px-6">
        <div className="container mx-auto">
          <motion.div
            initial="hidden"
            whileInView="visible"
            viewport={{ once: true }}
            variants={staggerContainer}
            className="max-w-5xl"
          >
            <motion.h1 
              variants={fadeInUp}
              className="text-6xl md:text-8xl lg:text-9xl font-extrabold leading-none mb-8 text-primary uppercase tracking-tighter"
            >
              Beyond <br /> 
              <span className="text-foreground">Humanity.</span>
            </motion.h1>
            
            <motion.p 
              variants={fadeInUp}
              className="text-xl md:text-2xl text-muted-foreground max-w-2xl font-light leading-relaxed mb-12"
            >
              Even if everyone disappears from the world, RESTILL-AI will solve everything. 
              We are the digital ghost in the machine, orchestrating the survival and evolution 
              of global enterprises through autonomous intelligence.
            </motion.p>
          </motion.div>
        </div>

        {/* Background Visual */}
        <div className="absolute top-0 right-0 w-1/2 h-full -z-10 opacity-20">
          <img 
            src={IMAGES.AI_NEURAL_6} 
            alt="Neural Architecture" 
            className="w-full h-full object-cover grayscale mix-blend-screen"
          />
          <div className="absolute inset-0 bg-gradient-to-l from-background via-transparent to-background"></div>
        </div>
      </section>

      {/* Stats Section */}
      <section className="py-20 bg-secondary/30 border-y border-border/50">
        <div className="container mx-auto px-6">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {stats.map((stat, index) => (
              <StatCard 
                key={index} 
                label={stat.label} 
                value={stat.value} 
                description={stat.description} 
              />
            ))}
          </div>
        </div>
      </section>

      {/* Orchestration Details */}
      <section className="py-24 px-6">
        <div className="container mx-auto">
          <div className="grid lg:grid-cols-2 gap-16 items-center">
            <motion.div
              initial={{ opacity: 0, x: -50 }}
              whileInView={{ opacity: 1, x: 0 }}
              transition={springPresets.gentle}
              viewport={{ once: true }}
            >
              <h2 className="text-4xl md:text-5xl font-bold mb-8 uppercase tracking-tight">
                The AI <span className="text-accent">Orchestration</span> Layer
              </h2>
              <div className="space-y-8">
                <div className="flex gap-6">
                  <div className="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center shrink-0 border border-primary/20">
                    <Cpu className="text-primary" />
                  </div>
                  <div>
                    <h3 className="text-xl font-bold mb-2">Multi-LLM Integration</h3>
                    <p className="text-muted-foreground">We leverage GPT-4, Claude 3.5, and Gemini Pro 1.5, custom-tuned with the RESTILL-AI proprietary core for Japanese business logic.</p>
                  </div>
                </div>
                <div className="flex gap-6">
                  <div className="w-12 h-12 rounded-full bg-accent/10 flex items-center justify-center shrink-0 border border-accent/20">
                    <BrainCircuit className="text-accent" />
                  </div>
                  <div>
                    <h3 className="text-xl font-bold mb-2">Claudecode Symphony</h3>
                    <p className="text-muted-foreground">Our developers utilize Claudecode-driven orchestration to automate complex software development lifecycles at 10x speed.</p>
                  </div>
                </div>
                <div className="flex gap-6">
                  <div className="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center shrink-0 border border-primary/20">
                    <Network className="text-primary" />
                  </div>
                  <div>
                    <h3 className="text-xl font-bold mb-2">320 Autonomous Agents</h3>
                    <p className="text-muted-foreground">A fleet of specialized AI agents working 24/7 across 23 global projects, ensuring zero downtime and maximum efficiency.</p>
                  </div>
                </div>
              </div>
            </motion.div>

            <motion.div
              initial={{ opacity: 0, scale: 0.9 }}
              whileInView={{ opacity: 1, scale: 1 }}
              transition={springPresets.gentle}
              viewport={{ once: true }}
              className="relative aspect-square rounded-2xl overflow-hidden border border-border group"
            >
              <img 
                src={IMAGES.AI_MCP_SYSTEM_20260212_055024_38} 
                alt="RESTILL-AI MCP System" 
                className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
              />
              <div className="absolute inset-0 bg-primary/20 mix-blend-overlay"></div>
              <div className="absolute bottom-8 left-8 right-8 p-6 bg-background/80 backdrop-blur-xl border border-white/10 rounded-xl">
                <p className="text-sm font-mono text-accent mb-2 uppercase">System Status: Optimal</p>
                <p className="text-lg font-medium">Continuous monitoring of 320 neural instances across Tokyo, San Francisco, and London.</p>
              </div>
            </motion.div>
          </div>
        </div>
      </section>

      {/* Team Section - The Architects */}
      <section className="py-24 bg-muted/20">
        <div className="container mx-auto px-6">
          <div className="mb-16 text-center">
            <h2 className="text-5xl md:text-6xl font-black mb-4 uppercase">The Architects</h2>
            <p className="text-muted-foreground text-lg">10 Human minds orchestrating 320 digital souls.</p>
          </div>

          <motion.div 
            variants={staggerContainer}
            initial="hidden"
            whileInView="visible"
            viewport={{ once: true }}
            className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8"
          >
            {team.map((member) => (
              <motion.div
                key={member.id}
                variants={staggerItem}
                className="group relative bg-card border border-border/50 rounded-2xl p-6 transition-all duration-300 hover:border-primary/50 hover:shadow-[0_0_30px_-10px_rgba(var(--primary),0.3)]"
              >
                <div className="aspect-square rounded-xl overflow-hidden mb-6 bg-secondary">
                  <img 
                    src={IMAGES[member.imageKey as keyof typeof IMAGES]} 
                    alt={member.name} 
                    className="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-500"
                  />
                </div>
                <h3 className="text-xl font-bold mb-1">{member.name}</h3>
                <p className="text-primary font-mono text-sm mb-4 uppercase tracking-widest">{member.role}</p>
                <p className="text-muted-foreground text-sm leading-relaxed line-clamp-3">{member.bio}</p>
                
                <div className="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                  <Zap className="w-5 h-5 text-accent" />
                </div>
              </motion.div>
            ))}
          </motion.div>
        </div>
      </section>

      {/* Vision Footer */}
      <section className="py-32 px-6 bg-black text-white text-center relative overflow-hidden">
        <div className="container mx-auto relative z-10">
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={springPresets.smooth}
            viewport={{ once: true }}
          >
            <h2 className="text-4xl md:text-7xl font-black mb-8 uppercase tracking-tighter">
              WE ARE THE <span className="text-primary">SINGULARITY</span>
            </h2>
            <p className="text-xl text-white/60 max-w-3xl mx-auto mb-12">
              RESTILL is not just a company. It is an evolutionary step in how businesses interact with existence. 
              Our architecture is eternal, our logic is absolute, and our agents are tireless.
            </p>
            <div className="flex flex-wrap justify-center gap-12 text-white/40 font-mono">
              <div className="flex items-center gap-2"><ShieldCheck className="w-5 h-5" /> SOVEREIGN</div>
              <div className="flex items-center gap-2"><Globe className="w-5 h-5" /> GLOBAL</div>
              <div className="flex items-center gap-2"><Zap className="w-5 h-5" /> INSTANT</div>
            </div>
          </motion.div>
        </div>

        {/* Abstract Background Light */}
        <div className="absolute -bottom-1/2 left-1/2 -translate-x-1/2 w-full h-full bg-primary/10 blur-[150px] rounded-full"></div>
      </section>
    </div>
  );
};

export default About;