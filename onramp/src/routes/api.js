const express = require('express');
const router = express.Router();

// Controllers will be added here
const transFiController = require('../controllers/transFiController');
const orderController = require('../controllers/orderController');

// TransFi API routes
router.get('/currencies', transFiController.getSupportedCurrencies);
router.get('/tokens', transFiController.getSupportedTokens);
router.get('/payment-methods', transFiController.getPaymentMethods);
router.post('/quote', transFiController.getQuote);

// Order routes
router.post('/order', orderController.createOrder);
router.get('/orders', orderController.getOrders);
router.get('/orders/:id', orderController.getOrderById);

// Webhook endpoint for TransFi notifications
router.post('/webhook', orderController.handleWebhook);

module.exports = router;
