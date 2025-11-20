const axios = require('axios');
const config = require('../config');

class TransFiService {
  constructor() {
    this.baseUrl = config.transfi.baseUrl;
    this.username = config.transfi.username;
    this.password = config.transfi.password;
    this.mid = config.transfi.mid;

    // Create Basic Auth credentials
    const credentials = `${this.username}:${this.password}`;
    const base64Credentials = Buffer.from(credentials).toString('base64');

    // Create axios instance with default config
    this.client = axios.create({
      baseURL: this.baseUrl,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Basic ${base64Credentials}`,
        'MID': this.mid
      }
    });

    console.log('✅ TransFi Service initialized');
    console.log(`   MID: ${this.mid}`);
    console.log(`   Base URL: ${this.baseUrl}`);
  }

  /**
   * Get list of supported currencies (Demo data)
   * Note: TransFi API doesn't provide a dedicated endpoint for this
   */
  async getSupportedCurrencies() {
    // Return demo data for supported fiat currencies
    return {
      currencies: [
        { code: 'JPY', name: '日本円', symbol: '¥' },
        { code: 'USD', name: 'US Dollar', symbol: '$' },
        { code: 'EUR', name: 'Euro', symbol: '€' },
        { code: 'GBP', name: 'British Pound', symbol: '£' }
      ]
    };
  }

  /**
   * Get list of supported tokens (Demo data)
   * Note: TransFi API doesn't provide a dedicated endpoint for this
   */
  async getSupportedTokens() {
    // Return demo data for supported crypto tokens
    return {
      tokens: [
        { code: 'USDT', name: 'Tether', network: 'Multiple' },
        { code: 'USDC', name: 'USD Coin', network: 'Multiple' },
        { code: 'BTC', name: 'Bitcoin', network: 'Bitcoin' },
        { code: 'ETH', name: 'Ethereum', network: 'Ethereum' }
      ]
    };
  }

  /**
   * Get list of payment methods
   * @param {string} currency - Currency code (e.g., 'JPY')
   * @param {string} direction - 'deposit' or 'withdraw'
   */
  async getPaymentMethods(currency = 'JPY', direction = 'deposit') {
    try {
      const response = await this.client.get('/v2/payment-methods', {
        params: {
          currency,
          direction
        }
      });
      return response.data;
    } catch (error) {
      this.handleError(error, 'Failed to fetch payment methods');
    }
  }

  /**
   * Get quote for currency exchange
   * @param {Object} params - Quote parameters
   * @param {string} params.sourceCurrency - Source fiat currency (e.g., 'JPY')
   * @param {string} params.targetCurrency - Target crypto currency (e.g., 'USDT')
   * @param {number} params.sourceAmount - Amount in source currency
   */
  async getQuote(params) {
    // Demo quote calculation (replace with actual API when available)
    // Simplified exchange rates for demo
    const rates = {
      'JPY-USDT': 0.0067,  // 1 JPY ≈ 0.0067 USDT
      'JPY-USDC': 0.0067,
      'JPY-BTC': 0.00000015,
      'JPY-ETH': 0.0000027,
      'USD-USDT': 1.0,
      'USD-USDC': 1.0,
      'EUR-USDT': 1.08,
      'GBP-USDT': 1.26
    };

    const rateKey = `${params.sourceCurrency}-${params.targetCurrency}`;
    const rate = rates[rateKey] || 0.0067;
    const targetAmount = params.sourceAmount * rate;

    return {
      source_currency: params.sourceCurrency,
      target_currency: params.targetCurrency,
      source_amount: params.sourceAmount,
      target_amount: targetAmount.toFixed(6),
      exchange_rate: rate,
      valid_until: new Date(Date.now() + 5 * 60 * 1000).toISOString() // 5 minutes
    };
  }

  /**
   * Create a fiat payin order (onramp)
   * @param {Object} orderData - Order details
   */
  async createOrder(orderData) {
    // Demo mode: return mock response instead of calling actual API
    const isDemoMode = config.nodeEnv === 'development' && !config.transfi.username;

    if (isDemoMode) {
      console.log('📝 Demo Mode: Creating mock order');
      return {
        order_id: `DEMO_ORDER_${Date.now()}`,
        status: 'pending',
        amount: orderData.amount,
        currency: orderData.currency,
        payment_url: `http://localhost:${config.port}/order-complete?order_id=DEMO_ORDER_${Date.now()}&status=pending`,
        created_at: new Date().toISOString()
      };
    }

    // Real API call
    try {
      const response = await this.client.post('/v2/orders/deposit', orderData);
      return response.data;
    } catch (error) {
      this.handleError(error, 'Failed to create order');
    }
  }

  /**
   * Get order status
   * @param {string} orderId - Order ID
   */
  async getOrderStatus(orderId) {
    try {
      const response = await this.client.get(`/orders/${orderId}`);
      return response.data;
    } catch (error) {
      this.handleError(error, 'Failed to get order status');
    }
  }

  /**
   * Error handler
   */
  handleError(error, message) {
    console.error(`TransFi API Error: ${message}`, error.response?.data || error.message);

    const errorResponse = {
      message,
      status: error.response?.status || 500,
      details: error.response?.data || error.message
    };

    throw new Error(JSON.stringify(errorResponse));
  }
}

module.exports = new TransFiService();
