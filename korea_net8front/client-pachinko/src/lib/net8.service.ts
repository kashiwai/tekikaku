// lib/net8.service.ts
import {
    GameStartRequest,
    GameStartResponse,
    GameEndRequest,
    GameEndResponse,
  } from '../types/net8';
  
  export class NET8Service {
    private baseUrl: string;
    private apiKey: string;
  
    constructor() {
      this.baseUrl = process.env.NET8_API_BASE_URL!;
      this.apiKey = process.env.NET8_API_KEY!;
    }
  
    private async fetchWithAuth(
      endpoint: string,
      options: RequestInit = {}
    ): Promise<Response> {
      const url = `${this.baseUrl}${endpoint}`;
      
      const defaultOptions: RequestInit = {
        headers: {
          'Authorization': `Bearer ${this.apiKey}`,
          'Content-Type': 'application/json',
          ...options.headers,
        },
        ...options,
      };
  
      const response = await fetch(url, defaultOptions);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
  
      return response;
    }
  
    async startGame(request: GameStartRequest): Promise<GameStartResponse> {
      const response = await this.fetchWithAuth('/api/v1/game_start.php', {
        method: 'POST',
        body: JSON.stringify(request),
      });
  
      return response.json();
    }
  
    async endGame(request: GameEndRequest): Promise<GameEndResponse> {
      const response = await this.fetchWithAuth('/api/v1/game_end.php', {
        method: 'POST',
        body: JSON.stringify(request),
      });
  
      return response.json();
    }
  
    async addPoints(
      userId: string,
      amount: number,
      reason?: string
    ): Promise<any> {
      const body: any = { userId, amount };
      if (reason) body.reason = reason;
  
      const response = await this.fetchWithAuth('/api/v1/add_points.php', {
        method: 'POST',
        body: JSON.stringify(body),
      });
  
      return response.json();
    }
  
    async getPlayHistory(
      userId: string,
      limit: number = 10,
      offset: number = 0
    ): Promise<any> {
      const response = await this.fetchWithAuth(
        `/api/v1/play_history.php?userId=${userId}&limit=${limit}&offset=${offset}`
      );
  
      return response.json();
    }
  }