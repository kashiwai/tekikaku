# NET8 SDK Detailed Implementation Guide (English)

**Version**: v1.1.0
**Last Updated**: 2025-11-24
**Audience**: Partner Company Developers & System Integrators
**Document Type**: Technical Implementation Guide

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Prerequisites](#prerequisites)
4. [Authentication System](#authentication-system)
5. [Detailed Implementation Steps](#detailed-implementation-steps)
6. [Security](#security)
7. [Performance Optimization](#performance-optimization)
8. [Production Migration](#production-migration)
9. [Operations Management](#operations-management)
10. [Best Practices](#best-practices)

---

## Overview

### What is NET8 SDK

NET8 SDK is a cloud-based SDK that enables remote operation of pachinko and pachislot gaming machines. Partner companies can use this SDK to provide real machine gaming experiences through their own web applications or mobile apps.

### Key Features

#### 1. Game Session Management
- **game_start**: Initiate games and consume points
- **game_end**: Complete games and award points
- **Session Tracking**: Real-time game state management

#### 2. User Management
- **Automatic User Linking**: Seamless integration between partner user IDs and NET8 users
- **Point Management**: Per-user point balance tracking
- **Play History**: Access to historical game data

#### 3. WebRTC Video Streaming
- **Real-time Video**: Low-latency streaming from machine cameras
- **PeerJS Integration**: Simplified WebRTC connection establishment
- **Mock Functionality**: Simulated video streaming in test environments

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                  Partner Company                             │
│  ┌────────────────────────────────────────────────────┐    │
│  │     Frontend (Web/Mobile App)                      │    │
│  │  - User Authentication                             │    │
│  │  - Game Selection UI                               │    │
│  │  - WebRTC Video Display                            │    │
│  └────────────────────────────────────────────────────┘    │
│                         ↓ HTTPS/WSS                         │
│  ┌────────────────────────────────────────────────────┐    │
│  │           Backend Server                            │    │
│  │  - NET8 SDK Integration                            │    │
│  │  - User Authentication                             │    │
│  │  - Session Management                              │    │
│  └────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                         ↓ API Key Authentication
┌─────────────────────────────────────────────────────────────┐
│                    NET8 Cloud                                │
│  ┌────────────────────────────────────────────────────┐    │
│  │            NET8 SDK API Gateway                     │    │
│  │  - Authentication & Authorization                   │    │
│  │  - Rate Limiting                                    │    │
│  │  - Logging                                          │    │
│  └────────────────────────────────────────────────────┘    │
│                         ↓                                    │
│  ┌────────────────────────────────────────────────────┐    │
│  │       Game Session Management Service               │    │
│  │  - Point Management                                 │    │
│  │  - Session Tracking                                 │    │
│  │  - Transaction Recording                            │    │
│  └────────────────────────────────────────────────────┘    │
│                         ↓                                    │
│  ┌────────────────────────────────────────────────────┐    │
│  │         WebRTC Signaling Server                     │    │
│  │  - PeerJS Server                                    │    │
│  │  - TURN/STUN Servers                                │    │
│  └────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                         ↓ WebRTC P2P
┌─────────────────────────────────────────────────────────────┐
│              Machine Location (Store)                        │
│  ┌────────────────────────────────────────────────────┐    │
│  │           Pachinko Machine                          │    │
│  │  - Camera Video Streaming                           │    │
│  │  - Control Reception                                │    │
│  └────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

### Data Flow

#### Game Start Flow
```
1. User clicks game start button
   ↓
2. Partner server calls game_start API
   - API Key authentication
   - User authentication
   - Point balance verification
   ↓
3. NET8 creates session
   - sessionId issuance
   - Point consumption (e.g., 100 points)
   - Available machine assignment
   ↓
4. WebRTC connection establishment
   - Signaling information retrieval
   - PeerJS connection setup
   - Video streaming starts
   ↓
5. User plays game
```

#### Game End Flow
```
1. User ends game or time expires
   ↓
2. Partner server calls game_end API
   - sessionId specification
   - Result (win/lose)
   - Points won
   ↓
3. NET8 processes points
   - Point addition
   - Transaction recording
   - Session closure
   ↓
4. WebRTC connection terminated
   ↓
5. Result screen displayed
```

---

## Architecture

### Detailed System Architecture

#### Layer Structure

```
┌─────────────────────────────────────────────┐
│      Presentation Layer                      │
│  - Frontend UI                               │
│  - WebRTC Video Display                      │
│  - User Interaction                          │
└─────────────────────────────────────────────┘
                    ↓ HTTPS
┌─────────────────────────────────────────────┐
│         Application Layer                    │
│  - Business Logic                            │
│  - Session Management                        │
│  - User Management                           │
└─────────────────────────────────────────────┘
                    ↓ REST API
┌─────────────────────────────────────────────┐
│      Integration Layer (NET8 SDK)            │
│  - API Key Authentication                    │
│  - Request/Response Transformation           │
│  - Error Handling                            │
└─────────────────────────────────────────────┘
                    ↓ HTTPS
┌─────────────────────────────────────────────┐
│        NET8 Cloud Services                   │
│  - API Gateway                               │
│  - Game Services                             │
│  - User Services                             │
└─────────────────────────────────────────────┘
                    ↓ SQL/NoSQL
┌─────────────────────────────────────────────┐
│          Data Persistence Layer              │
│  - MySQL (GCP Cloud SQL)                     │
│  - Transaction Logs                          │
│  - Session State                             │
└─────────────────────────────────────────────┘
```

### Security Architecture

#### Defense in Depth Strategy

```
1. Network Layer
   ├─ TLS 1.2+ Enforced
   ├─ DDoS Protection (Cloudflare)
   └─ IP Restrictions (Optional)

2. Authentication Layer
   ├─ API Key Authentication (Bearer Token)
   ├─ Rate Limiting (1000 req/hour)
   └─ Request Signing (Optional)

3. Application Layer
   ├─ Input Validation
   ├─ SQL Injection Prevention
   ├─ XSS Prevention
   └─ CSRF Protection

4. Data Layer
   ├─ Database Encryption
   ├─ Principle of Least Privilege
   └─ Audit Logs
```

---

## Prerequisites

### 1. System Requirements

#### Server Requirements

**Minimum Requirements**:
- **OS**: Linux (Ubuntu 20.04+), macOS, Windows Server 2019+
- **CPU**: 2 cores or more
- **Memory**: 4GB or more
- **Storage**: 20GB+ free space
- **Network**: 100Mbps+ bandwidth

**Recommended Requirements**:
- **OS**: Linux (Ubuntu 22.04 LTS)
- **CPU**: 4 cores or more
- **Memory**: 8GB or more
- **Storage**: SSD 50GB+
- **Network**: 1Gbps+ bandwidth

#### Software Requirements

**Required**:
- **Programming Languages**:
  - Node.js 16.x+
  - PHP 7.4+
  - Python 3.8+
  - Java 11+
  - Ruby 2.7+
  - Go 1.18+
- **Database**: MySQL 8.0, PostgreSQL 13+, MongoDB 5.0+
- **Web Server**: Nginx, Apache, IIS
- **SSL Certificate**: Let's Encrypt recommended

**Recommended**:
- **Containers**: Docker 20.10+, Kubernetes 1.24+
- **CI/CD**: GitHub Actions, GitLab CI, Jenkins
- **Monitoring**: Prometheus, Grafana, Datadog
- **Logging**: ELK Stack, Splunk

#### Client Requirements

**Web Browsers**:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Opera 76+

**Mobile**:
- iOS 14+ (Safari, Chrome)
- Android 8+ (Chrome, Firefox)

**Network**:
- Minimum: 5Mbps
- Recommended: 10Mbps+
- WebRTC compatible (UDP hole punching capable)

### 2. API Key Acquisition

#### Acquisition Process

1. **Contact NET8 Sales**
   ```
   Email: sales@net8.com
   Phone: +81-3-XXXX-XXXX
   Business Hours: Weekdays 10:00-18:00 (JST)
   ```

2. **Contract Agreement**
   - Review terms of service
   - Sign NDA (Non-Disclosure Agreement)
   - Execute contract

3. **API Key Issuance**
   - Immediate issuance of test environment key (`pk_test_xxx`)
   - Production environment key (`pk_live_xxx`) after approval

4. **Setup Support**
   - Technical support contact information
   - Onboarding session scheduling
   - Sample code provision

#### API Key Types

| Type | Prefix | Purpose | Rate Limit | Real Machine |
|------|--------|---------|-----------|--------------|
| Demo | `pk_demo_` | Evaluation & Demo | 100 req/hour | ❌ Mock only |
| Test | `pk_test_` | Development & Testing | 1000 req/hour | ❌ Mock only |
| Production | `pk_live_` | Production Use | 10000 req/hour | ✅ Real machines |

#### API Key Management

**Storage Location**:
```bash
# Environment Variables (Recommended)
export NET8_API_KEY="pk_test_abc123def456..."

# .env File
NET8_API_KEY=pk_test_abc123def456...
NET8_API_BASE=https://mgg-webservice-production.up.railway.app

# Secrets Manager (Production Recommended)
AWS Secrets Manager
Google Cloud Secret Manager
Azure Key Vault
```

**Security Notes**:
- ✅ Use only on server-side
- ✅ Manage via environment variables or Secrets Manager
- ✅ Add to .gitignore (never commit to Git)
- ❌ Never embed in frontend JavaScript
- ❌ Never output to logs
- ❌ Never share with third parties

### 3. Development Environment Setup

#### Node.js Environment

**Project Initialization**:
```bash
# Create project directory
mkdir my-net8-app
cd my-net8-app

# Create package.json
npm init -y

# Install dependencies
npm install axios dotenv express peerjs-client

# TypeScript setup (recommended)
npm install --save-dev typescript @types/node @types/express
npx tsc --init
```

**Directory Structure**:
```
my-net8-app/
├── src/
│   ├── config/
│   │   └── net8.config.ts       # NET8 configuration
│   ├── services/
│   │   ├── net8.service.ts      # NET8 SDK wrapper
│   │   └── webrtc.service.ts    # WebRTC management
│   ├── controllers/
│   │   └── game.controller.ts   # Game controller
│   ├── middleware/
│   │   └── auth.middleware.ts   # Authentication middleware
│   └── app.ts                   # Main application
├── .env                         # Environment variables
├── .env.example                 # Environment variable template
├── .gitignore
├── package.json
└── tsconfig.json
```

**Environment Variables** (`.env`):
```bash
# NET8 SDK Configuration
NET8_API_KEY=pk_test_abc123def456...
NET8_API_BASE=https://mgg-webservice-production.up.railway.app
NET8_ENVIRONMENT=test

# Application Configuration
APP_PORT=3000
APP_ENV=development

# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=my_app
DB_USER=app_user
DB_PASSWORD=secure_password

# WebRTC Configuration
PEERJS_HOST=dockerfilesignaling-production.up.railway.app
PEERJS_PORT=443
PEERJS_SECURE=true
PEERJS_KEY=peerjs
```

---

## Authentication System

### API Key Authentication Details

#### Authentication Flow

```
1. Client → Partner Server
   - User login
   - Session token issuance

2. Partner Server → NET8 API
   ┌─────────────────────────────────┐
   │ Authorization: Bearer pk_test_...│
   │ Content-Type: application/json  │
   │                                 │
   │ {                               │
   │   "userId": "user_12345",       │
   │   "modelId": "HOKUTO4GO"        │
   │ }                               │
   └─────────────────────────────────┘
              ↓
3. NET8 API Gateway
   - API Key validation
   - Rate limit check
   - User existence verification
              ↓
4. Response
   ┌─────────────────────────────────┐
   │ HTTP/1.1 200 OK                 │
   │ Content-Type: application/json  │
   │                                 │
   │ {                               │
   │   "success": true,              │
   │   "sessionId": "gs_xxx...",     │
   │   "newBalance": 9900            │
   │ }                               │
   └─────────────────────────────────┘
```

#### Authorization Header Format

**Correct Format**:
```
Authorization: Bearer pk_test_abc123def456789
```

**Incorrect Formats**:
```
❌ Authorization: pk_test_abc123def456789        # Missing "Bearer "
❌ Authorization: Bearer: pk_test_abc123def456789 # Extra colon
❌ X-API-Key: pk_test_abc123def456789             # Wrong header name
```

#### Implementation Examples

**JavaScript/TypeScript**:
```typescript
import axios from 'axios';

const API_BASE = 'https://mgg-webservice-production.up.railway.app';
const API_KEY = process.env.NET8_API_KEY;

const client = axios.create({
  baseURL: API_BASE,
  headers: {
    'Authorization': `Bearer ${API_KEY}`,
    'Content-Type': 'application/json'
  },
  timeout: 30000
});

// Send request
async function startGame(userId: string, modelId: string) {
  try {
    const response = await client.post('/api/v1/game_start.php', {
      userId,
      modelId
    });
    return response.data;
  } catch (error) {
    console.error('Game start failed:', error);
    throw error;
  }
}
```

**PHP**:
```php
<?php
use GuzzleHttp\Client;

$apiKey = getenv('NET8_API_KEY');
$apiBase = 'https://mgg-webservice-production.up.railway.app';

$client = new Client([
    'base_uri' => $apiBase,
    'headers' => [
        'Authorization' => "Bearer {$apiKey}",
        'Content-Type' => 'application/json'
    ],
    'timeout' => 30
]);

function startGame($userId, $modelId) {
    global $client;

    try {
        $response = $client->post('/api/v1/game_start.php', [
            'json' => [
                'userId' => $userId,
                'modelId' => $modelId
            ]
        ]);

        return json_decode($response->getBody(), true);
    } catch (\Exception $e) {
        error_log("Game start failed: " . $e->getMessage());
        throw $e;
    }
}
?>
```

**Python**:
```python
import os
import requests

API_KEY = os.getenv('NET8_API_KEY')
API_BASE = 'https://mgg-webservice-production.up.railway.app'

headers = {
    'Authorization': f'Bearer {API_KEY}',
    'Content-Type': 'application/json'
}

def start_game(user_id: str, model_id: str):
    try:
        response = requests.post(
            f'{API_BASE}/api/v1/game_start.php',
            headers=headers,
            json={
                'userId': user_id,
                'modelId': model_id
            },
            timeout=30
        )
        response.raise_for_status()
        return response.json()
    except requests.RequestException as e:
        print(f'Game start failed: {e}')
        raise
```

### Rate Limiting

#### Limit Details

| Plan | Requests | Period | On Exceed |
|------|----------|--------|-----------|
| Demo | 100 | 1 hour | 429 Too Many Requests |
| Test | 1,000 | 1 hour | 429 Too Many Requests |
| Production | 10,000 | 1 hour | 429 Too Many Requests |

#### Rate Limit Headers

```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 950
X-RateLimit-Reset: 1700000000
```

#### Rate Limit Error Handling

```typescript
async function requestWithRetry<T>(
  fn: () => Promise<T>,
  maxRetries: number = 3
): Promise<T> {
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await fn();
    } catch (error: any) {
      if (error.response?.status === 429) {
        const retryAfter = error.response.headers['retry-after'] || Math.pow(2, i);
        console.log(`Rate limited. Retrying after ${retryAfter}s...`);
        await new Promise(resolve => setTimeout(resolve, retryAfter * 1000));
      } else {
        throw error;
      }
    }
  }
  throw new Error('Max retries exceeded');
}

// Usage
const gameStart = await requestWithRetry(() =>
  client.post('/api/v1/game_start.php', { userId, modelId })
);
```

---

## Detailed Implementation Steps

### Step 1: Project Setup

#### 1-1. Install Dependencies

**Node.js + TypeScript**:
```bash
npm install axios dotenv peerjs-client
npm install --save-dev @types/node @types/peerjs
```

**package.json**:
```json
{
  "name": "my-net8-app",
  "version": "1.0.0",
  "scripts": {
    "dev": "ts-node src/app.ts",
    "build": "tsc",
    "start": "node dist/app.js"
  },
  "dependencies": {
    "axios": "^1.6.0",
    "dotenv": "^16.3.0",
    "peerjs-client": "^1.5.0",
    "express": "^4.18.0"
  },
  "devDependencies": {
    "@types/node": "^20.0.0",
    "@types/express": "^4.17.0",
    "typescript": "^5.0.0"
  }
}
```

#### 1-2. TypeScript Configuration

**tsconfig.json**:
```json
{
  "compilerOptions": {
    "target": "ES2020",
    "module": "commonjs",
    "lib": ["ES2020"],
    "outDir": "./dist",
    "rootDir": "./src",
    "strict": true,
    "esModuleInterop": true,
    "skipLibCheck": true,
    "forceConsistentCasingInFileNames": true,
    "resolveJsonModule": true
  },
  "include": ["src/**/*"],
  "exclude": ["node_modules", "dist"]
}
```

### Step 2: NET8 SDK Wrapper Implementation

#### 2-1. Configuration File

**src/config/net8.config.ts**:
```typescript
import dotenv from 'dotenv';
dotenv.config();

export interface Net8Config {
  apiKey: string;
  apiBase: string;
  environment: 'demo' | 'test' | 'live';
  timeout: number;
  retryAttempts: number;
  peerjsHost: string;
  peerjsPort: number;
  peerjsSecure: boolean;
}

export const net8Config: Net8Config = {
  apiKey: process.env.NET8_API_KEY || '',
  apiBase: process.env.NET8_API_BASE || 'https://mgg-webservice-production.up.railway.app',
  environment: (process.env.NET8_ENVIRONMENT as any) || 'test',
  timeout: parseInt(process.env.NET8_TIMEOUT || '30000'),
  retryAttempts: parseInt(process.env.NET8_RETRY_ATTEMPTS || '3'),
  peerjsHost: process.env.PEERJS_HOST || 'dockerfilesignaling-production.up.railway.app',
  peerjsPort: parseInt(process.env.PEERJS_PORT || '443'),
  peerjsSecure: process.env.PEERJS_SECURE === 'true'
};

// Configuration validation
if (!net8Config.apiKey) {
  throw new Error('NET8_API_KEY is required');
}
```

#### 2-2. NET8 SDK Service

**src/services/net8.service.ts**:
```typescript
import axios, { AxiosInstance, AxiosError } from 'axios';
import { net8Config } from '../config/net8.config';

export interface GameStartRequest {
  userId: string;
  modelId: string;
}

export interface GameStartResponse {
  success: boolean;
  sessionId: string;
  newBalance: number;
  pointsConsumed: number;
  memberNo: number;
  machine: {
    id: number;
    modelId: string;
    modelName: string;
  };
  webrtc: {
    peerId: string;
    signalingServer: string;
    stunServers: string[];
    turnServers: any[];
  };
}

export interface GameEndRequest {
  sessionId: string;
  result: 'win' | 'lose';
  pointsWon: number;
}

export interface GameEndResponse {
  success: boolean;
  newBalance: number;
  transaction: {
    id: number;
    balanceBefore: number;
    balanceAfter: number;
    pointsWon: number;
  };
}

export class Net8Service {
  private client: AxiosInstance;

  constructor() {
    this.client = axios.create({
      baseURL: net8Config.apiBase,
      headers: {
        'Authorization': `Bearer ${net8Config.apiKey}`,
        'Content-Type': 'application/json'
      },
      timeout: net8Config.timeout
    });

    // Request interceptor
    this.client.interceptors.request.use(
      (config) => {
        console.log(`[NET8] ${config.method?.toUpperCase()} ${config.url}`);
        return config;
      },
      (error) => {
        console.error('[NET8] Request error:', error);
        return Promise.reject(error);
      }
    );

    // Response interceptor
    this.client.interceptors.response.use(
      (response) => {
        console.log(`[NET8] Response ${response.status}:`, response.data);
        return response;
      },
      (error: AxiosError) => {
        console.error('[NET8] Response error:', error.response?.data);
        return Promise.reject(error);
      }
    );
  }

  /**
   * Start game
   */
  async startGame(request: GameStartRequest): Promise<GameStartResponse> {
    try {
      const response = await this.client.post<GameStartResponse>(
        '/api/v1/game_start.php',
        request
      );
      return response.data;
    } catch (error) {
      this.handleError(error, 'Game start failed');
      throw error;
    }
  }

  /**
   * End game
   */
  async endGame(request: GameEndRequest): Promise<GameEndResponse> {
    try {
      const response = await this.client.post<GameEndResponse>(
        '/api/v1/game_end.php',
        request
      );
      return response.data;
    } catch (error) {
      this.handleError(error, 'Game end failed');
      throw error;
    }
  }

  /**
   * Error handling
   */
  private handleError(error: any, context: string): void {
    if (axios.isAxiosError(error)) {
      const statusCode = error.response?.status;
      const errorData = error.response?.data;

      switch (statusCode) {
        case 401:
          console.error(`[NET8] ${context}: Invalid API Key`);
          break;
        case 429:
          console.error(`[NET8] ${context}: Rate limit exceeded`);
          break;
        case 500:
          console.error(`[NET8] ${context}: Internal server error`);
          break;
        default:
          console.error(`[NET8] ${context}:`, errorData);
      }
    } else {
      console.error(`[NET8] ${context}:`, error);
    }
  }
}
```

---

## Security

### Security Best Practices

#### 1. API Key Protection

**Use Environment Variables**:
```bash
# .env
NET8_API_KEY=pk_live_abc123...

# .gitignore
.env
.env.local
.env.production
```

**Use Secrets Manager (Production Recommended)**:
```typescript
// AWS Secrets Manager
import { SecretsManagerClient, GetSecretValueCommand } from '@aws-sdk/client-secrets-manager';

async function getApiKey() {
  const client = new SecretsManagerClient({ region: 'ap-northeast-1' });
  const response = await client.send(
    new GetSecretValueCommand({ SecretId: 'net8/api-key' })
  );
  return JSON.parse(response.SecretString).apiKey;
}
```

#### 2. HTTPS Enforcement

**Express Middleware**:
```typescript
app.use((req, res, next) => {
  if (req.headers['x-forwarded-proto'] !== 'https' && process.env.NODE_ENV === 'production') {
    return res.redirect(`https://${req.headers.host}${req.url}`);
  }
  next();
});
```

#### 3. CORS Configuration

**Proper CORS Setup**:
```typescript
import cors from 'cors';

app.use(cors({
  origin: ['https://yourdomain.com', 'https://app.yourdomain.com'],
  credentials: true,
  methods: ['GET', 'POST'],
  allowedHeaders: ['Content-Type', 'Authorization']
}));
```

#### 4. Input Validation

**Validation Middleware**:
```typescript
import { body, validationResult } from 'express-validator';

app.post('/game/start',
  body('userId').isString().notEmpty(),
  body('modelId').isString().notEmpty(),
  (req, res, next) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }
    next();
  },
  async (req, res) => {
    // Game start logic
  }
);
```

---

## Performance Optimization

### 1. Connection Pooling

**Database Connection Pool**:
```typescript
import mysql from 'mysql2/promise';

const pool = mysql.createPool({
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});
```

### 2. Caching

**Redis Cache**:
```typescript
import Redis from 'ioredis';

const redis = new Redis({
  host: process.env.REDIS_HOST,
  port: parseInt(process.env.REDIS_PORT || '6379')
});

// Cache user balance
async function getUserBalance(userId: string): Promise<number> {
  const cached = await redis.get(`balance:${userId}`);
  if (cached) {
    return parseInt(cached);
  }

  // Fetch from DB
  const balance = await fetchBalanceFromDB(userId);

  // Save to cache (60 seconds)
  await redis.setex(`balance:${userId}`, 60, balance.toString());

  return balance;
}
```

---

## Production Migration

### Deployment Checklist

#### Environment Setup
- [ ] Obtain production API Key (pk_live_xxx)
- [ ] Configure environment variables for production
- [ ] Setup HTTPS certificates
- [ ] Verify database connection information

#### Security
- [ ] Encrypt API Key storage
- [ ] Verify CORS configuration
- [ ] Configure rate limiting
- [ ] Sanitize log outputs

#### Performance
- [ ] Setup connection pooling
- [ ] Implement caching strategy
- [ ] Configure CDN

#### Monitoring
- [ ] Setup error monitoring (Sentry, etc.)
- [ ] Configure performance monitoring
- [ ] Setup log aggregation
- [ ] Configure alerts

---

## Best Practices

1. **Error Handling**: Wrap all API calls in try-catch blocks
2. **Logging**: Record important events with structured logging
3. **Testing**: Always implement unit and integration tests
4. **Documentation**: Maintain comprehensive code comments and README
5. **Monitoring**: Always use monitoring tools in production

---

**© 2025 NET8. All rights reserved.**
