import { Client, Stat, Service, TeamMember } from '@/lib/index';

export const clients: Client[] = [
  { id: 'c1', name: 'FUJI TELEVISION', industry: 'Media & Entertainment' },
  { id: 'c2', name: 'NETFLIX', industry: 'Streaming & Content' },
  { id: 'c3', name: 'AMAZON', industry: 'Cloud & Commerce' },
  { id: 'c4', name: 'HITACHI', industry: 'Industrial Systems' },
  { id: 'c5', name: 'PANASONIC', industry: 'Electronics' },
  { id: 'c6', name: 'TESLA', industry: 'Automotive & Energy' },
  { id: 'c7', name: 'DENTSU', industry: 'Advertising & Marketing' },
  { id: 'c8', name: 'CYBERAGENT', industry: 'Digital Media' },
];

export const stats: Stat[] = [
  {
    label: 'AI AGENTS',
    value: '320',
    description: 'Autonomous agents active across global networks.',
  },
  {
    label: 'OPERATIONS',
    value: '23',
    description: 'Simultaneous high-stakes projects in execution.',
  },
  {
    label: 'ARCHITECTS',
    value: '10',
    description: 'Elite human specialists orchestrating the future.',
  },
];

export const services: Service[] = [
  {
    id: 's1',
    title: 'RESTILL-AI CONSULTING',
    category: 'AI Consulting',
    description: 'Proprietary AI agents built on GPT, Claude, and Gemini, specialized in Japanese business culture and operational excellence.',
    iconName: 'BrainCircuit',
  },
  {
    id: 's2',
    title: 'AI ORCHESTRATION',
    category: 'AI Orchestration',
    description: 'Deployment of Claudecode and advanced orchestration frameworks to automate the entire software development lifecycle.',
    iconName: 'Cpu',
  },
  {
    id: 's3',
    title: 'DIGITAL SOVEREIGNTY',
    category: 'Digital Sovereignty',
    description: 'Providing autonomous solutions that ensure business continuity even in a post-human landscape. The RESTILL legacy.',
    iconName: 'ShieldAlert',
  },
];

export const team: TeamMember[] = [
  {
    id: 't1',
    name: 'K/K',
    role: 'CTO & CAIO',
    bio: 'Chief Technology Officer & Chief AI Officer. Pioneer in neural orchestration and the visionary behind the RESTILL-AI core.',
    imageKey: 'CTO_KK_AVATAR_20260212_055024_35',
  },
  {
    id: 't2',
    name: 'A/A',
    role: 'CMTO',
    bio: 'Chief Machine Technology Officer. Expert in multi-LLM alignment and autonomous agent behavior modeling.',
    imageKey: 'CMTO_AA_FEMALE_20260212_055040_39',
  },
  {
    id: 't3',
    name: 'H/M',
    role: 'CMTO',
    bio: 'Chief Machine Technology Officer. Specializing in Claudecode integration and automated development workflows.',
    imageKey: 'CMTO_HM_FEMALE_20260212_055037_40',
  },
  {
    id: 't4',
    name: 'T/E',
    role: 'CZO',
    bio: 'Chief Zero Officer. Ensuring digital sovereignty through hardened AI infrastructure and zero-latency operations.',
    imageKey: 'CZO_TE_AVATAR_20260212_055024_36',
  },
  {
    id: 't5',
    name: 'T/A',
    role: 'CWO',
    bio: 'Chief World Officer. Crafting the personality and efficiency parameters for our 320 AI agents across global networks.',
    imageKey: 'CWO_TA_AVATAR_20260212_055025_37',
  },
  {
    id: 't6',
    name: 'SECRET AGENT 01',
    role: 'Strategic Operations',
    bio: 'Classified operative bridging human intent with machine execution for top-tier Japanese clients.',
    imageKey: 'AI_NEURAL_9',
  },
  {
    id: 't7',
    name: 'SECRET AGENT 02',
    role: 'Quantum Consultant',
    bio: 'Classified specialist explaining the unexplainable. Making AI logic accessible to C-suite executives.',
    imageKey: 'AI_NEURAL_8',
  },
  {
    id: 't8',
    name: 'SECRET AGENT 03',
    role: 'Protocol Lead',
    bio: 'Classified engineer defining communication standards between Gemini, Claude, and GPT within our ecosystem.',
    imageKey: 'AI_NEURAL_7',
  },
  {
    id: 't9',
    name: 'SECRET AGENT 04',
    role: 'Automation Engineer',
    bio: 'Classified operative focusing on zero-latency deployment for concurrent project loads.',
    imageKey: 'AI_NEURAL_6',
  },
  {
    id: 't10',
    name: 'RESTILL-AI MCP',
    role: 'Multi-Agent Coordination Protocol',
    bio: 'The autonomous AI system orchestrating all 320 agents. Self-improving, self-governing, eternal.',
    imageKey: 'AI_MCP_SYSTEM_20260212_055024_38',
  },
];
