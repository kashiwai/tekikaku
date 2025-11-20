const transFiService = require('../services/transFiService');
const database = require('../models/database');

/**
 * Create a new order
 */
exports.createOrder = async (req, res, next) => {
  try {
    const {
      sourceCurrency,
      targetCurrency,
      sourceAmount,
      walletAddress,
      paymentMethod
    } = req.body;

    // Validation
    if (!sourceCurrency || !targetCurrency || !sourceAmount || !walletAddress) {
      return res.status(400).json({
        success: false,
        error: 'Missing required parameters: sourceCurrency, targetCurrency, sourceAmount, walletAddress'
      });
    }

    // First get a quote
    const quote = await transFiService.getQuote({
      sourceCurrency,
      targetCurrency,
      sourceAmount: parseFloat(sourceAmount)
    });

    // Create order with TransFi API format
    const orderData = {
      // User information (demo data - should come from user registration/KYC in production)
      firstName: req.body.firstName || 'Demo',
      lastName: req.body.lastName || 'User',
      email: req.body.email || 'demo@example.com',
      country: req.body.country || 'JP',
      type: 'individual',

      // Transaction details
      amount: parseFloat(sourceAmount),
      currency: sourceCurrency,
      paymentType: paymentMethod || 'bank_transfer',
      purposeCode: req.body.purposeCode || 'personal',

      // Partner identification
      partnerId: `ORDER_${Date.now()}`,

      // Redirect URL
      redirectUrl: `${req.protocol}://${req.get('host')}/order-complete`,

      // Crypto withdrawal details (instant settlement to crypto)
      withdrawDetails: {
        cryptoTicker: targetCurrency,
        walletAddress: walletAddress
      }
    };

    const transFiOrder = await transFiService.createOrder(orderData);

    // Save to database
    const dbOrder = await database.insertOrder({
      order_id: transFiOrder.order_id || `ORDER_${Date.now()}`,
      source_currency: sourceCurrency,
      target_currency: targetCurrency,
      source_amount: parseFloat(sourceAmount),
      target_amount: quote.target_amount || null,
      exchange_rate: quote.exchange_rate || null,
      status: transFiOrder.status || 'pending',
      payment_method: paymentMethod,
      user_wallet_address: walletAddress,
      transfi_response: transFiOrder
    });

    res.json({
      success: true,
      data: {
        orderId: dbOrder.order_id,
        status: transFiOrder.status,
        paymentUrl: transFiOrder.payment_url,
        quote: quote
      }
    });

  } catch (error) {
    console.error('Create order error:', error);

    // Parse error message if it's a JSON string
    let errorMessage = '注文の作成に失敗しました';
    try {
      const parsedError = JSON.parse(error.message);
      errorMessage = parsedError.details?.message || parsedError.message || errorMessage;
    } catch (e) {
      errorMessage = error.message || errorMessage;
    }

    return res.status(400).json({
      success: false,
      error: errorMessage
    });
  }
};

/**
 * Get all orders
 */
exports.getOrders = async (req, res, next) => {
  try {
    const limit = parseInt(req.query.limit) || 50;
    const orders = await database.getAllOrders(limit);

    res.json({
      success: true,
      data: orders
    });
  } catch (error) {
    next(error);
  }
};

/**
 * Get order by ID
 */
exports.getOrderById = async (req, res, next) => {
  try {
    const { id } = req.params;
    const order = await database.getOrderById(id);

    if (!order) {
      return res.status(404).json({
        success: false,
        error: 'Order not found'
      });
    }

    res.json({
      success: true,
      data: order
    });
  } catch (error) {
    next(error);
  }
};

/**
 * Webhook handler for TransFi notifications
 */
exports.handleWebhook = async (req, res, next) => {
  try {
    const webhookData = req.body;
    console.log('📨 Webhook received:', webhookData);

    // Update order status based on webhook
    if (webhookData.order_id) {
      await database.updateOrder(webhookData.order_id, {
        status: webhookData.status || 'processing',
        webhook_data: webhookData
      });
    }

    // Respond to TransFi
    res.status(200).json({ received: true });

  } catch (error) {
    console.error('Webhook error:', error);
    next(error);
  }
};
