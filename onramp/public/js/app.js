// API base URL
const API_BASE = '/api';

// State
let currentQuote = null;

// DOM Elements
const orderForm = document.getElementById('orderForm');
const getQuoteBtn = document.getElementById('getQuoteBtn');
const submitBtn = document.getElementById('submitBtn');
const quoteSection = document.getElementById('quoteSection');
const messageDiv = document.getElementById('message');
const loadingDiv = document.getElementById('loading');
const ordersListDiv = document.getElementById('ordersList');

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadOrders();
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    getQuoteBtn.addEventListener('click', handleGetQuote);
    orderForm.addEventListener('submit', handleSubmitOrder);
}

// Show message
function showMessage(message, type = 'success') {
    messageDiv.textContent = message;
    messageDiv.className = `message ${type}`;
    setTimeout(() => {
        messageDiv.style.display = 'none';
    }, 5000);
}

// Show/hide loading
function setLoading(isLoading) {
    loadingDiv.style.display = isLoading ? 'block' : 'none';
    getQuoteBtn.disabled = isLoading;
    submitBtn.disabled = isLoading || !currentQuote;
}

// Get quote
async function handleGetQuote() {
    try {
        const sourceAmount = document.getElementById('sourceAmount').value;
        const sourceCurrency = document.getElementById('sourceCurrency').value;
        const targetCurrency = document.getElementById('targetCurrency').value;

        if (!sourceAmount || !targetCurrency) {
            showMessage('すべての項目を入力してください', 'error');
            return;
        }

        setLoading(true);

        const response = await fetch(`${API_BASE}/quote`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                sourceCurrency,
                targetCurrency,
                sourceAmount: parseFloat(sourceAmount)
            })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error?.message || '見積もりの取得に失敗しました');
        }

        // Display quote
        currentQuote = data.data;
        displayQuote(currentQuote);
        submitBtn.disabled = false;
        showMessage('見積もりを取得しました', 'success');

    } catch (error) {
        console.error('Quote error:', error);
        showMessage(error.message, 'error');
        currentQuote = null;
        submitBtn.disabled = true;
    } finally {
        setLoading(false);
    }
}

// Display quote
function displayQuote(quote) {
    const targetCurrency = document.getElementById('targetCurrency').value;

    document.getElementById('quoteRate').textContent =
        quote.exchange_rate ? `1 JPY = ${quote.exchange_rate} ${targetCurrency}` : '計算中...';

    document.getElementById('quoteAmount').textContent =
        quote.target_amount ? `${quote.target_amount} ${targetCurrency}` : '計算中...';

    quoteSection.style.display = 'block';
}

// Submit order
async function handleSubmitOrder(e) {
    e.preventDefault();

    if (!currentQuote) {
        showMessage('先に見積もりを取得してください', 'error');
        return;
    }

    try {
        const formData = new FormData(orderForm);
        const orderData = {
            sourceCurrency: formData.get('sourceCurrency'),
            targetCurrency: formData.get('targetCurrency'),
            sourceAmount: parseFloat(formData.get('sourceAmount')),
            walletAddress: formData.get('walletAddress'),
            firstName: formData.get('firstName'),
            lastName: formData.get('lastName'),
            email: formData.get('email')
        };

        setLoading(true);

        const response = await fetch(`${API_BASE}/order`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData)
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error?.message || '注文の作成に失敗しました');
        }

        showMessage('注文が作成されました！', 'success');

        // Reset form
        orderForm.reset();
        quoteSection.style.display = 'none';
        currentQuote = null;
        submitBtn.disabled = true;

        // Reload orders
        setTimeout(() => loadOrders(), 1000);

        // If there's a payment URL, redirect
        if (data.data.paymentUrl) {
            setTimeout(() => {
                window.location.href = data.data.paymentUrl;
            }, 2000);
        }

    } catch (error) {
        console.error('Order error:', error);
        showMessage(error.message, 'error');
    } finally {
        setLoading(false);
    }
}

// Load orders
async function loadOrders() {
    try {
        const response = await fetch(`${API_BASE}/orders?limit=10`);
        const data = await response.json();

        if (!response.ok) {
            throw new Error('取引履歴の読み込みに失敗しました');
        }

        displayOrders(data.data);

    } catch (error) {
        console.error('Load orders error:', error);
        ordersListDiv.innerHTML = '<p>取引履歴の読み込みに失敗しました</p>';
    }
}

// Display orders
function displayOrders(orders) {
    if (!orders || orders.length === 0) {
        ordersListDiv.innerHTML = '<p>まだ取引履歴がありません</p>';
        return;
    }

    ordersListDiv.innerHTML = orders.map(order => {
        const createdAt = new Date(order.created_at).toLocaleString('ja-JP');
        return `
            <div class="order-item">
                <p><strong>注文ID:</strong> ${order.order_id}</p>
                <p><strong>金額:</strong> ${order.source_amount} ${order.source_currency} → ${order.target_amount || '処理中'} ${order.target_currency}</p>
                <p><strong>ステータス:</strong> ${getStatusText(order.status)}</p>
                <p><strong>作成日時:</strong> ${createdAt}</p>
            </div>
        `;
    }).join('');
}

// Get status text
function getStatusText(status) {
    const statusMap = {
        'pending': '保留中',
        'processing': '処理中',
        'completed': '完了',
        'failed': '失敗'
    };
    return statusMap[status] || status;
}
