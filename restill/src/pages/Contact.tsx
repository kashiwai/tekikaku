import React from 'react';
import { motion } from 'framer-motion';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { Mail, MapPin, MessageCircle, Send, Terminal, Globe, Cpu, Layers, BrainCircuit, Network, ShieldCheck } from 'lucide-react';
import { IMAGES } from '@/assets/images';
import { supabase } from '@/integrations/supabase/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Card } from '@/components/ui/card';
import { useToast } from '@/components/ui/use-toast';
const contactSchema = z.object({
  name: z.string().min(2, {
    message: "Identification required."
  }),
  email: z.string().email({
    message: "Invalid neural address."
  }),
  organization: z.string().min(2, {
    message: "Origin entity required."
  }),
  message: z.string().min(10, {
    message: "Input transmission too brief."
  })
});
type ContactFormValues = z.infer<typeof contactSchema>;
const Contact = () => {
  const {
    toast
  } = useToast();
  const {
    register,
    handleSubmit,
    reset,
    formState: {
      errors,
      isSubmitting
    }
  } = useForm<ContactFormValues>({
    resolver: zodResolver(contactSchema)
  });
  const onSubmit = async (data: ContactFormValues) => {
    try {
      // Insert data into Supabase
      const { error } = await supabase
        .from('contact_inquiries_2026_02_12_14_20')
        .insert([{
          name: data.name,
          email: data.email,
          organization: data.organization,
          message: data.message
        }]);

      if (error) {
        throw error;
      }

      toast({
        title: "TRANSMISSION SUCCESSFUL",
        description: "RESTILL-AI has acknowledged your signal. Expect an interface soon.",
      });
      reset();
    } catch (error) {
      console.error('Error submitting form:', error);
      toast({
        title: "TRANSMISSION FAILED",
        description: "Neural network interference detected. Please retry transmission.",
        variant: "destructive",
      });
    }
  };
  return <div className="min-h-screen bg-background text-foreground overflow-hidden">
      {/* Background Neural Glow */}
      <div className="fixed inset-0 pointer-events-none opacity-20">
        <div className="absolute top-[-10%] right-[-10%] w-[50%] h-[50%] bg-primary/30 blur-[120px] rounded-full" />
        <div className="absolute bottom-[-10%] left-[-10%] w-[40%] h-[40%] bg-accent/20 blur-[100px] rounded-full" />
      </div>

      <div className="container mx-auto px-4 py-24 relative z-10">
        {/* Header Section */}
        <div className="mb-20">
          <motion.div initial={{
          opacity: 0,
          x: -50
        }} animate={{
          opacity: 1,
          x: 0
        }} transition={{
          duration: 0.8,
          ease: "easeOut"
        }}>
            <span className="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-primary/30 bg-primary/5 text-primary text-xs font-mono mb-6 uppercase tracking-widest">
              <Terminal className="w-3 h-3" /> Neural Interface Active
            </span>
            <h1 className="text-7xl md:text-9xl font-extrabold tracking-tighter leading-none mb-8">
              CONTACT<br />
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-primary via-accent to-primary animate-pulse">
                RESTILL
              </span>
            </h1>
            <p className="max-w-2xl text-xl text-muted-foreground font-light leading-relaxed">
              Whether you are human or machine, our AI agents are ready to orchestrate your next evolution. 
              Initiate the connection below.
            </p>
          </motion.div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-16">
          {/* Form Section */}
          <motion.div initial={{
          opacity: 0,
          y: 30
        }} animate={{
          opacity: 1,
          y: 0
        }} transition={{
          delay: 0.2,
          duration: 0.8
        }}>
            <Card className="p-8 md:p-12 border-border/50 bg-card/50 backdrop-blur-xl relative overflow-hidden group">
              <div className="absolute top-0 right-0 p-4">
                <Layers className="w-8 h-8 text-primary/20 group-hover:text-primary/40 transition-colors" />
              </div>
              
              <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="space-y-2">
                    <Label htmlFor="name" className="text-xs uppercase tracking-widest font-mono">Identity</Label>
                    <Input id="name" placeholder="YOUR NAME" {...register('name')} className="bg-background/50 border-border/40 focus:border-primary transition-all" />
                    {errors.name && <p className="text-destructive text-[10px] font-mono">{errors.name.message}</p>}
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="email" className="text-xs uppercase tracking-widest font-mono">Neural Address</Label>
                    <Input id="email" type="email" placeholder="EMAIL@PROTOCOL.COM" {...register('email')} className="bg-background/50 border-border/40 focus:border-primary transition-all" />
                    {errors.email && <p className="text-destructive text-[10px] font-mono">{errors.email.message}</p>}
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="organization" className="text-xs uppercase tracking-widest font-mono">Origin Entity</Label>
                  <Input id="organization" placeholder="COMPANY / ORGANIZATION" {...register('organization')} className="bg-background/50 border-border/40 focus:border-primary transition-all" />
                  {errors.organization && <p className="text-destructive text-[10px] font-mono">{errors.organization.message}</p>}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="message" className="text-xs uppercase tracking-widest font-mono">Input Transmission</Label>
                  <Textarea id="message" placeholder="DESCRIBE THE ORCHESTRATION..." rows={5} {...register('message')} className="bg-background/50 border-border/40 focus:border-primary transition-all resize-none" />
                  {errors.message && <p className="text-destructive text-[10px] font-mono">{errors.message.message}</p>}
                </div>

                <Button type="submit" disabled={isSubmitting} className="w-full h-14 text-lg font-bold tracking-widest bg-primary hover:bg-primary/90 text-primary-foreground group">
                  {isSubmitting ? <span className="flex items-center gap-2 animate-pulse">TRANSMITTING...</span> : <span className="flex items-center gap-2">
                      INITIATE INTERFACE <Send className="w-4 h-4 group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform" />
                    </span>}
                </Button>
              </form>
              
              {/* AI Chat Alternative */}
              <div className="mt-8 p-6 rounded-xl bg-accent/5 border border-accent/20">
                <div className="flex items-center gap-3 mb-4">
                  <MessageCircle className="w-5 h-5 text-accent" />
                  <h3 className="font-bold text-sm uppercase tracking-widest">Instant AI Chat</h3>
                </div>
                <p className="text-sm text-muted-foreground mb-4">
                  Prefer immediate response? Our RESTILL-AI agents are available 24/7 for instant consultation.
                </p>
                <Button variant="outline" className="w-full border-accent/30 text-accent hover:bg-accent/10" onClick={() => window.open('mailto:support@restill.biz?subject=AI Chat Request', '_blank')}>
                  Start AI Chat Session
                </Button>
              </div>
            </Card>
          </motion.div>

          {/* Info Section */}
          <motion.div initial={{
          opacity: 0,
          x: 50
        }} animate={{
          opacity: 1,
          x: 0
        }} transition={{
          delay: 0.4,
          duration: 0.8
        }} className="flex flex-col justify-between space-y-12">
            {/* Company Details */}
            <div className="space-y-12">
              <div>
                <h3 className="text-xs font-mono uppercase tracking-[0.3em] text-primary mb-6">HQ Location</h3>
                <div className="flex items-start gap-4">
                  <div className="p-3 rounded-lg bg-secondary border border-border/50">
                    <MapPin className="w-6 h-6 text-accent" />
                  </div>
                  <div>
                    <p className="text-2xl font-semibold mb-2">Tokyo Command Center</p>
                    <p className="text-muted-foreground leading-relaxed">call to AI MCP</p>
                  </div>
                </div>
              </div>

              {/* Business Model Overview */}
              <div className="mb-12">
                <h3 className="text-xs font-mono uppercase tracking-[0.3em] text-primary mb-6">Business Model</h3>
                <div className="grid grid-cols-1 gap-6">
                  <div className="flex items-start gap-4 p-4 rounded-lg bg-card/30 border border-border/30">
                    <div className="p-2 rounded-lg bg-primary/10 border border-primary/20">
                      <BrainCircuit className="w-5 h-5 text-primary" />
                    </div>
                    <div>
                      <h4 className="font-bold text-sm mb-1">AI CONSULTING</h4>
                      <p className="text-xs text-muted-foreground">Custom AI agents for Japanese business operations</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-4 p-4 rounded-lg bg-card/30 border border-border/30">
                    <div className="p-2 rounded-lg bg-accent/10 border border-accent/20">
                      <Network className="w-5 h-5 text-accent" />
                    </div>
                    <div>
                      <h4 className="font-bold text-sm mb-1">AI ORCHESTRATION</h4>
                      <p className="text-xs text-muted-foreground">Automated development lifecycle management</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-4 p-4 rounded-lg bg-card/30 border border-border/30">
                    <div className="p-2 rounded-lg bg-primary/10 border border-primary/20">
                      <ShieldCheck className="w-5 h-5 text-primary" />
                    </div>
                    <div>
                      <h4 className="font-bold text-sm mb-1">DIGITAL SOVEREIGNTY</h4>
                      <p className="text-xs text-muted-foreground">Post-human business continuity solutions</p>
                    </div>
                  </div>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div className="space-y-4">
                  <h3 className="text-xs font-mono uppercase tracking-[0.3em] text-primary">Communication</h3>
                  <div className="flex items-center gap-3 text-lg">
                    <Mail className="w-5 h-5 text-accent" />
                    <span>support@restill.biz</span>
                  </div>
                  <div className="flex items-center gap-3 text-lg">
                    <MessageCircle className="w-5 h-5 text-accent" />
                    <span>AI Chat Available 24/7</span>
                  </div>
                </div>

                <div className="space-y-4">
                  <h3 className="text-xs font-mono uppercase tracking-[0.3em] text-primary">Global Status</h3>
                  <div className="flex items-center gap-3 text-lg">
                    <Globe className="w-5 h-5 text-accent" />
                    <span>Operational (24/7)</span>
                  </div>
                  <div className="flex items-center gap-3 text-lg">
                    <Cpu className="w-5 h-5 text-accent" />
                    <span>320 Active Agents</span>
                  </div>
                </div>
              </div>
            </div>

            {/* Visual Teaser */}
            <div className="relative aspect-video rounded-2xl overflow-hidden border border-border/50 group">
              <img src={IMAGES.CYBER_TECH_4} alt="RESTILL Command Center" className="object-cover w-full h-full grayscale group-hover:grayscale-0 transition-all duration-1000 scale-105 group-hover:scale-100" />
              <div className="absolute inset-0 bg-gradient-to-t from-background via-transparent to-transparent" />
              <div className="absolute bottom-4 left-4">
                <p className="text-xs font-mono text-primary/80">NODE_ID: RESTILL_HQ_0404</p>
                <p className="text-xs font-mono text-primary/80">LAT: 35.7013° N | LONG: 139.7029° E</p>
              </div>
            </div>
          </motion.div>
        </div>

        {/* Map Placeholder / Stylized Section */}
        <motion.div initial={{
        opacity: 0
      }} whileInView={{
        opacity: 1
      }} viewport={{
        once: true
      }} className="mt-24 py-12 border-y border-border/30">
          <div className="flex flex-col md:flex-row items-center justify-between gap-8 opacity-60 hover:opacity-100 transition-opacity">
            <p className="font-mono text-sm tracking-widest">
              RESTILL-AI CO., LTD. // SHINJUKU-KU, TOKYO // EST. 2026
            </p>
            <div className="flex gap-8">
              <span className="text-xs font-mono uppercase tracking-widest hover:text-primary cursor-pointer">LinkedIn</span>
              <span className="text-xs font-mono uppercase tracking-widest hover:text-primary cursor-pointer">X (Twitter)</span>
              <span className="text-xs font-mono uppercase tracking-widest hover:text-primary cursor-pointer">GitHub</span>
            </div>
          </div>
        </motion.div>
      </div>
    </div>;
};
export default Contact;