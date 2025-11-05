# WindowsStore制御用のレジストリキーを作成
# 既に存在する場合エラーとなるため、-Force オプションを指定
New-Item "HKLM:\SOFTWARE\Policies\Microsoft\WindowsStore" -Force

# レジストリエントリを作成してWindowsStoreの自動更新を無効にする(4:有効 / 2:無効)
# 既に存在する場合エラーとなるため、-Force オプションを指定
New-ItemProperty -Path "HKLM:\SOFTWARE\Policies\Microsoft\WindowsStore" -Name "AutoDownload" -PropertyType Dword -Value 2 -Force


# 終了
exit 0
