# コンピュータ名の一部を構成するパラメータの取得
Param(
    [int]$clientno
  , [int]$hostno
)

# コンピュータ名の生成
[string] $ComputerName = "CAMERA-" + ([string]$clientno).PadLeft(3, "0") + "-" + ([string]$hostno).PadLeft(4, "0")


# コンピュータ名の変更
Rename-Computer -NewName $ComputerName


# 終了
exit 0
