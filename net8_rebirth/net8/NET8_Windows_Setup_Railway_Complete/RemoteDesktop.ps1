# リモートデスクトップの有効化
Set-ItemProperty -Path "HKLM:\SYSTEM\CurrentControlSet\Control\Terminal Server" -Name "fDenyTSConnections" -Type Dword -Value "0"

# ネットワークレベル認証の無効化(必要があれば)
Set-ItemProperty -Path "HKLM:\SYSTEM\CurrentControlSet\Control\Terminal Server\WinStations\RDP-Tcp" -Name "UserAuthentication" -Type Dword -Value "0"

# RDPをコマンドで有効化すると、自動的にWindowsDefenderファイアウォールの設定がされないため、穴を開ける
#Set-NetFirewallRule -DisplayName "リモート デスクトップ - シャドウ (TCP 受信)" -Enabled True
#Set-NetFirewallRule -DisplayName "リモート デスクトップ - ユーザー モード (TCP 受信)" -Enabled True
#Set-NetFirewallRule -DisplayName "リモート デスクトップ - ユーザー モード (UDP 受信)" -Enabled True
Set-NetFirewallRule -DisplayName "リモート デスクトップ - シャドウ (TCP 受信)" -Profile Domain,Private -Enabled True
Set-NetFirewallRule -DisplayName "リモート デスクトップ - ユーザー モード (TCP 受信)" -Profile Domain,Private -Enabled True
Set-NetFirewallRule -DisplayName "リモート デスクトップ - ユーザー モード (UDP 受信)" -Profile Domain,Private -Enabled True


# 終了
exit 0
