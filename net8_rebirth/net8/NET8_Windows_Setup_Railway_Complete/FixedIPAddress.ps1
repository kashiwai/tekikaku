# デフォルトゲートウェイ : 基準IPが属する帯域の .1
# ホストIP               : 基準IPにホスト番号が足されたもの
# サブネットマスク       : /24(255.255.255.0) 固定
# DNS                    : デフォルトゲートウェイと一緒
# やっつけで書いている(ネットワーク的計算をしてない)ので、末尾 .254 を超える範囲はおかしくなるぉ


# IP設定に必要なパラメータの取得
Param(
    [string]$baseip
  , [int]$hostno
)

# パラメータで指定された基準IPを分解
$IPs = $baseip.Split(".")
[string] $NetworkAdd = $IPs[0] + "." + $IPs[1] + "." + $IPs[2] + "."

# デフォルトゲートウェイ決定
[string] $GateWay = $NetworkAdd + "1"

# ホストIP決定
[int] $HostNum = ([int]$IPs[3]) + $hostno
[string] $HostIP = $NetworkAdd + [string]$HostNum

# サブネットマスクは /24(255.255.255.0) 固定
[int] $Mask = 24


# 1つしかNICを持たない前提でGet-NetAdapterで取得したNICをそのままNew-NetIPAddress/Set-DnsClientServerAddressにリダイレクト
#   複数NICを持つ環境下では、↓のようにMACアドレスとかNIC名で特定する必要あり
#   Get-NetAdapter | ? MacAddress -eq 9C-7B-EF-B2-03-AA | New-NetIPAddress ...
#   Get-NetAdapter -Name "イーサネット" | New-NetIPAddress ...

# v6の無効化
Get-NetAdapter | Disable-NetAdapterBinding -ComponentID "ms_tcpip6"
# こっちの書き方とどちらが良いのだろう・・・
# Get-NetAdapter | Set-NetAdapterBinding -ComponentID "ms_tcpip6" -Enable $False

# DHCPサーバーを使っている状態から手動でIPアドレス(v4)を設定
Get-NetAdapter | New-NetIPAddress -AddressFamily IPv4 -IPAddress $HostIP -PrefixLength $Mask -DefaultGateway $GateWay

# 参照DNS設定
Get-NetAdapter | Set-DnsClientServerAddress -ServerAddresses $GateWay

# 初期時点ではパブリックネットワークになっているはずなので、プライベートに変更
Get-NetConnectionProfile | Set-NetConnectionProfile -NetworkCategory Private
#Get-NetConnectionProfile | where Name -eq "ネットワーク" | Set-NetConnectionProfile -NetworkCategory Private
#Get-NetConnectionProfile | where InterfaceAlias -eq "イーサネット" | Set-NetConnectionProfile -NetworkCategory Private


# 終了
exit 0
