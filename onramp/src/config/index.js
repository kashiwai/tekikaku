require('dotenv').config();

module.exports = {
  // Server Config
  port: process.env.PORT || 3000,
  nodeEnv: process.env.NODE_ENV || 'development',

  // TransFi API Config
  transfi: {
    username: process.env.TRANSFI_API_USERNAME || '',
    password: process.env.TRANSFI_API_PASSWORD || '',
    mid: process.env.TRANSFI_MID || '',
    baseUrl: process.env.TRANSFI_BASE_URL || 'https://sandbox-api.transfi.com',
    webhookSecret: process.env.TRANSFI_WEBHOOK_SECRET || '',
  },

  // Database Config
  database: {
    path: process.env.DB_PATH || './data/onramp.db'
  }
};
