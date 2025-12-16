#!/usr/bin/env node

const axios = require('axios');

const NET8_API_BASE_URL = 'https://mgg-webservice-production.up.railway.app';
const NET8_API_KEY = 'pk_demo_12345';

async function testConnection() {
  console.log('Testing NET8 API Connection...');
  console.log('Base URL:', NET8_API_BASE_URL);
  console.log('API Key:', NET8_API_KEY.substring(0, 10) + '...');
  
  try {
    const response = await axios.get(`${NET8_API_BASE_URL}/api/v1/health`, {
      headers: {
        'Authorization': `Bearer ${NET8_API_KEY}`,
        'Content-Type': 'application/json'
      },
      timeout: 10000
    });
    
    console.log('✅ Connection successful!');
    console.log('Response status:', response.status);
    console.log('Response data:', JSON.stringify(response.data, null, 2));
  } catch (error) {
    if (error.response) {
      console.log('❌ API responded with error:');
      console.log('Status:', error.response.status);
      console.log('Data:', error.response.data);
    } else if (error.request) {
      console.log('❌ No response received from API');
      console.log('This could mean the server is down or unreachable');
    } else {
      console.log('❌ Error setting up request:', error.message);
    }
  }
}

testConnection();