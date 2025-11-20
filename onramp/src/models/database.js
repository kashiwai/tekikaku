const sqlite3 = require('sqlite3').verbose();
const config = require('../config');
const path = require('path');

class Database {
  constructor() {
    this.db = null;
  }

  initialize() {
    return new Promise((resolve, reject) => {
      const dbPath = path.resolve(config.database.path);

      this.db = new sqlite3.Database(dbPath, (err) => {
        if (err) {
          console.error('Database connection error:', err);
          reject(err);
          return;
        }

        console.log('✅ Connected to SQLite database');
        this.createTables()
          .then(resolve)
          .catch(reject);
      });
    });
  }

  createTables() {
    return new Promise((resolve, reject) => {
      const createOrdersTable = `
        CREATE TABLE IF NOT EXISTS orders (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          order_id TEXT UNIQUE NOT NULL,
          source_currency TEXT NOT NULL,
          target_currency TEXT NOT NULL,
          source_amount REAL NOT NULL,
          target_amount REAL,
          exchange_rate REAL,
          status TEXT DEFAULT 'pending',
          payment_method TEXT,
          user_wallet_address TEXT,
          transfi_response TEXT,
          webhook_data TEXT,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
      `;

      this.db.run(createOrdersTable, (err) => {
        if (err) {
          console.error('Error creating orders table:', err);
          reject(err);
          return;
        }
        console.log('✅ Orders table ready');
        resolve();
      });
    });
  }

  // Insert new order
  insertOrder(orderData) {
    return new Promise((resolve, reject) => {
      const sql = `
        INSERT INTO orders (
          order_id, source_currency, target_currency,
          source_amount, target_amount, exchange_rate,
          status, payment_method, user_wallet_address, transfi_response
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      `;

      const params = [
        orderData.order_id,
        orderData.source_currency,
        orderData.target_currency,
        orderData.source_amount,
        orderData.target_amount || null,
        orderData.exchange_rate || null,
        orderData.status || 'pending',
        orderData.payment_method || null,
        orderData.user_wallet_address || null,
        JSON.stringify(orderData.transfi_response || {})
      ];

      this.db.run(sql, params, function(err) {
        if (err) {
          reject(err);
          return;
        }
        resolve({ id: this.lastID, order_id: orderData.order_id });
      });
    });
  }

  // Update order
  updateOrder(orderId, updateData) {
    return new Promise((resolve, reject) => {
      const sql = `
        UPDATE orders
        SET status = ?, webhook_data = ?, updated_at = CURRENT_TIMESTAMP
        WHERE order_id = ?
      `;

      const params = [
        updateData.status,
        JSON.stringify(updateData.webhook_data || {}),
        orderId
      ];

      this.db.run(sql, params, function(err) {
        if (err) {
          reject(err);
          return;
        }
        resolve({ changes: this.changes });
      });
    });
  }

  // Get all orders
  getAllOrders(limit = 50) {
    return new Promise((resolve, reject) => {
      const sql = `
        SELECT * FROM orders
        ORDER BY created_at DESC
        LIMIT ?
      `;

      this.db.all(sql, [limit], (err, rows) => {
        if (err) {
          reject(err);
          return;
        }
        resolve(rows);
      });
    });
  }

  // Get order by ID
  getOrderById(orderId) {
    return new Promise((resolve, reject) => {
      const sql = `SELECT * FROM orders WHERE order_id = ?`;

      this.db.get(sql, [orderId], (err, row) => {
        if (err) {
          reject(err);
          return;
        }
        resolve(row);
      });
    });
  }

  close() {
    if (this.db) {
      this.db.close();
    }
  }
}

const database = new Database();
module.exports = database;
