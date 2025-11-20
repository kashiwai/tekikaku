const transFiService = require('../services/transFiService');

/**
 * Get supported currencies
 */
exports.getSupportedCurrencies = async (req, res, next) => {
  try {
    const currencies = await transFiService.getSupportedCurrencies();
    res.json({
      success: true,
      data: currencies
    });
  } catch (error) {
    next(error);
  }
};

/**
 * Get supported tokens
 */
exports.getSupportedTokens = async (req, res, next) => {
  try {
    const tokens = await transFiService.getSupportedTokens();
    res.json({
      success: true,
      data: tokens
    });
  } catch (error) {
    next(error);
  }
};

/**
 * Get payment methods
 */
exports.getPaymentMethods = async (req, res, next) => {
  try {
    const methods = await transFiService.getPaymentMethods();
    res.json({
      success: true,
      data: methods
    });
  } catch (error) {
    next(error);
  }
};

/**
 * Get exchange rate quote
 */
exports.getQuote = async (req, res, next) => {
  try {
    const { sourceCurrency, targetCurrency, sourceAmount } = req.body;

    // Validation
    if (!sourceCurrency || !targetCurrency || !sourceAmount) {
      return res.status(400).json({
        success: false,
        error: 'Missing required parameters: sourceCurrency, targetCurrency, sourceAmount'
      });
    }

    const quote = await transFiService.getQuote({
      sourceCurrency,
      targetCurrency,
      sourceAmount: parseFloat(sourceAmount)
    });

    res.json({
      success: true,
      data: quote
    });
  } catch (error) {
    next(error);
  }
};
