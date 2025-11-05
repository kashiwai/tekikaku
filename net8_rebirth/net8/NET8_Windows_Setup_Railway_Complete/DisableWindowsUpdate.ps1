# WindowsUpdate制御用のレジストリキーを作成
# 既に存在する場合エラーとなるため、-Force オプションを指定
New-Item "HKLM:\SOFTWARE\Policies\Microsoft\Windows\WindowsUpdate\AU" -Force

# レジストリエントリを作成してWindowsUpdateを無効にする(0:有効 / 1:無効)
# 既に存在する場合エラーとなるため、-Force オプションを指定
New-ItemProperty -Path "HKLM:\SOFTWARE\Policies\Microsoft\Windows\WindowsUpdate\AU" -Name "NoAutoUpdate" -PropertyType Dword -Value 1 -Force


# 終了
exit 0
