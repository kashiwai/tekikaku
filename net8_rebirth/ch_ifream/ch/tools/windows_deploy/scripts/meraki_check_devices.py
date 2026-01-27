#!/usr/bin/env python3
"""
Meraki API - デバイス状態確認
"""

import requests
import json

API_KEY = "eb1b908e8ad8326f04faf9e3fc8e81efafd14058"
BASE_URL = "https://api.meraki.com/api/v1"
ORG_ID = "1738952"

HEADERS = {
    "X-Cisco-Meraki-API-Key": API_KEY,
    "Content-Type": "application/json"
}

print("=" * 50)
print("Meraki デバイス状態確認")
print("=" * 50)
print()

# 組織のデバイス一覧
print("1. 組織内のデバイス一覧:")
url = f"{BASE_URL}/organizations/{ORG_ID}/devices"
response = requests.get(url, headers=HEADERS)
print(f"   Status: {response.status_code}")
if response.status_code == 200:
    devices = response.json()
    print(f"   デバイス数: {len(devices)}")
    for d in devices:
        print(f"   - {d.get('model', '?')} / {d.get('name', '?')} / {d.get('serial', '?')}")
else:
    print(f"   Error: {response.text}")
print()

# 組織のインベントリ
print("2. インベントリ:")
url = f"{BASE_URL}/organizations/{ORG_ID}/inventoryDevices"
response = requests.get(url, headers=HEADERS)
print(f"   Status: {response.status_code}")
if response.status_code == 200:
    inventory = response.json()
    print(f"   デバイス数: {len(inventory)}")
    for d in inventory:
        print(f"   - {d.get('model', '?')} / Serial: {d.get('serial', '?')} / Network: {d.get('networkId', 'なし')}")
else:
    print(f"   Error: {response.text}")
print()

# 組織のライセンス状態
print("3. ライセンス状態:")
url = f"{BASE_URL}/organizations/{ORG_ID}/licenses/overview"
response = requests.get(url, headers=HEADERS)
print(f"   Status: {response.status_code}")
if response.status_code == 200:
    print(f"   {json.dumps(response.json(), indent=2)}")
else:
    print(f"   Error: {response.text}")
print()

# ネットワーク一覧（再確認）
print("4. ネットワーク一覧:")
url = f"{BASE_URL}/organizations/{ORG_ID}/networks"
response = requests.get(url, headers=HEADERS)
print(f"   Status: {response.status_code}")
if response.status_code == 200:
    networks = response.json()
    print(f"   ネットワーク数: {len(networks)}")
    for n in networks:
        print(f"   - {n.get('name', '?')} (ID: {n.get('id', '?')})")
        print(f"     Type: {n.get('productTypes', [])}")
else:
    print(f"   Error: {response.text}")
