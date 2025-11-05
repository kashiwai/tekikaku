# パブリックネットワークになっているはずなので、プライベートに変更
Get-NetConnectionProfile | Set-NetConnectionProfile -NetworkCategory Private


# 終了
exit 0
