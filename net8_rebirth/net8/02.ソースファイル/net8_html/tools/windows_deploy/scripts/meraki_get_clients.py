#!/usr/bin/env python3
"""
Meraki API - クライアント一覧取得スクリプト
DHCPで接続中の全デバイスのMAC/IPを取得
"""

import requests
import json
import csv
import os

# ============================================
# 設定
# ============================================
API_KEY = "eb1b908e8ad8326f04faf9e3fc8e81efafd14058"
BASE_URL = "https://api.meraki.com/api/v1"

HEADERS = {
    "X-Cisco-Meraki-API-Key": API_KEY,
    "Content-Type": "application/json"
}

# ============================================
# API関数
# ============================================

def get_organizations():
    """組織一覧を取得"""
    url = f"{BASE_URL}/organizations"
    response = requests.get(url, headers=HEADERS)
    response.raise_for_status()
    return response.json()

def get_networks(org_id):
    """ネットワーク一覧を取得"""
    url = f"{BASE_URL}/organizations/{org_id}/networks"
    response = requests.get(url, headers=HEADERS)
    response.raise_for_status()
    return response.json()

def get_clients(network_id, timespan=86400):
    """クライアント一覧を取得（過去24時間）"""
    url = f"{BASE_URL}/networks/{network_id}/clients"
    params = {"timespan": timespan}
    response = requests.get(url, headers=HEADERS, params=params)
    response.raise_for_status()
    return response.json()

def get_dhcp_subnets(network_id):
    """VLANとDHCP設定を取得"""
    url = f"{BASE_URL}/networks/{network_id}/appliance/vlans"
    response = requests.get(url, headers=HEADERS)
    if response.status_code == 400:
        # VLANが無効な場合、単一LAN設定を取得
        url = f"{BASE_URL}/networks/{network_id}/appliance/singleLan"
        response = requests.get(url, headers=HEADERS)
    response.raise_for_status()
    return response.json()

# ============================================
# メイン処理
# ============================================

def main():
    print("=" * 50)
    print("Meraki クライアント一覧取得")
    print("=" * 50)
    print()

    # 1. 組織を取得
    print("組織を取得中...")
    orgs = get_organizations()

    if not orgs:
        print("組織が見つかりません")
        return

    print(f"組織数: {len(orgs)}")
    for i, org in enumerate(orgs):
        print(f"  [{i+1}] {org['name']} (ID: {org['id']})")
    print()

    # 最初の組織を使用（複数ある場合は選択が必要）
    org_id = orgs[0]['id']
    org_name = orgs[0]['name']
    print(f"使用組織: {org_name}")
    print()

    # 2. ネットワークを取得
    print("ネットワークを取得中...")
    networks = get_networks(org_id)

    if not networks:
        print("ネットワークが見つかりません")
        return

    print(f"ネットワーク数: {len(networks)}")
    for i, net in enumerate(networks):
        print(f"  [{i+1}] {net['name']} (ID: {net['id']})")
    print()

    # 3. 各ネットワークのクライアントを取得
    all_clients = []

    for net in networks:
        network_id = net['id']
        network_name = net['name']

        print(f"クライアント取得中: {network_name}...")

        try:
            clients = get_clients(network_id)
            print(f"  → {len(clients)} 台検出")

            for client in clients:
                all_clients.append({
                    'network': network_name,
                    'description': client.get('description', ''),
                    'mac': client.get('mac', ''),
                    'ip': client.get('ip', ''),
                    'vlan': client.get('vlan', ''),
                    'manufacturer': client.get('manufacturer', ''),
                    'os': client.get('os', ''),
                    'status': client.get('status', '')
                })
        except Exception as e:
            print(f"  → エラー: {e}")

    print()
    print("=" * 50)
    print(f"合計: {len(all_clients)} 台")
    print("=" * 50)
    print()

    # 4. 結果を表示
    print("検出されたクライアント:")
    print("-" * 80)
    print(f"{'MAC':<20} {'IP':<16} {'メーカー':<20} {'説明'}")
    print("-" * 80)

    for client in all_clients:
        mac = client['mac']
        ip = client['ip'] or '(なし)'
        manufacturer = client['manufacturer'][:18] if client['manufacturer'] else ''
        desc = client['description'] or ''
        print(f"{mac:<20} {ip:<16} {manufacturer:<20} {desc}")

    # 5. CSVに保存
    script_dir = os.path.dirname(os.path.abspath(__file__))
    csv_path = os.path.join(script_dir, "..", "config", "meraki_clients.csv")

    with open(csv_path, 'w', newline='', encoding='utf-8') as f:
        writer = csv.DictWriter(f, fieldnames=[
            'machine_no', 'network', 'description', 'mac', 'ip',
            'vlan', 'manufacturer', 'os', 'status', 'model_name'
        ])
        writer.writeheader()

        for i, client in enumerate(all_clients, 1):
            client['machine_no'] = ''  # 手動で入力
            client['model_name'] = ''  # 手動で入力
            writer.writerow(client)

    print()
    print(f"CSVに保存しました: {csv_path}")
    print()
    print("次のステップ:")
    print("1. CSVを開いて machine_no と model_name を入力")
    print("2. DHCP予約を設定")

if __name__ == "__main__":
    main()
