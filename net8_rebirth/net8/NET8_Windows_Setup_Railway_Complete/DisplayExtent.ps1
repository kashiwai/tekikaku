# フォルダオプションの"登録されている拡張子は表示しない"のチェックを外す
Set-ItemProperty "HKCU:\Software\Microsoft\Windows\CurrentVersion\Explorer\Advanced" -Name "HideFileExt" -Value 0 -Force

# フォルダオプションの"自動的に現在のフォルダーまで展開する"のチェックを入れる
Set-ItemProperty "HKCU:\Software\Microsoft\Windows\CurrentVersion\Explorer\Advanced" -Name "NavPaneExpandToCurrentFolder" -Value 1 -Force


# 終了
exit 0
