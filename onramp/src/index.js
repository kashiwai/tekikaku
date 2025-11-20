const express = require('express');
const cors = require('cors');
const bodyParser = require('body-parser');
const path = require('path');
const config = require('./config');
const database = require('./models/database');

const app = express();

// Middleware
app.use(cors());
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.use(express.static(path.join(__dirname, '../public')));

// Routes
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, '../public/widget.html'));
});

// API Integration page (original)
app.get('/api-demo', (req, res) => {
  res.sendFile(path.join(__dirname, '../public/index.html'));
});

// Widget page
app.get('/widget', (req, res) => {
  res.sendFile(path.join(__dirname, '../public/widget.html'));
});

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({
    status: 'ok',
    environment: config.nodeEnv,
    timestamp: new Date().toISOString()
  });
});

// Order completion redirect page
app.get('/order-complete', (req, res) => {
  res.sendFile(path.join(__dirname, '../public/order-complete.html'));
});

// API Routes (will be added)
const apiRoutes = require('./routes/api');
app.use('/api', apiRoutes);

// Error handling middleware
app.use((err, req, res, next) => {
  console.error('Error:', err);
  res.status(err.status || 500).json({
    error: {
      message: err.message || 'Internal Server Error',
      ...(config.nodeEnv === 'development' && { stack: err.stack })
    }
  });
});

// 404 handler
app.use((req, res) => {
  res.status(404).json({ error: 'Not Found' });
});

// Initialize database and start server
database.initialize()
  .then(() => {
    const server = app.listen(config.port, () => {
      console.log(`🚀 TransFi Onramp Server running on port ${config.port}`);
      console.log(`📍 Environment: ${config.nodeEnv}`);
      console.log(`🌐 Access at: http://localhost:${config.port}`);
      console.log(`\n📌 Configuration URLs for TransFi Dashboard:`);
      console.log(`   Webhook URL: http://localhost:${config.port}/api/webhook`);
      console.log(`   Redirect URL: http://localhost:${config.port}/order-complete`);
    });
  })
  .catch(err => {
    console.error('Failed to initialize database:', err);
    process.exit(1);
  });

module.exports = app;
