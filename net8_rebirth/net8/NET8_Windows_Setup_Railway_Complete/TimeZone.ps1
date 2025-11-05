# タイムゾーン設定に必要なパラメータの取得
Param(
    [string]$country
)

#タイムゾーンIDの決定
[string] $timezoneid = $country + " Standard Time"

# タイムゾーンを設定
#     日本のタイムゾーンは「(UTC+09:00) 大阪、札幌、東京」: Tokyo Standard Time
#     台湾のタイムゾーンは「(UTC+08:00) 台北」            : Taipei Standard Time
Set-TimeZone -Id $timezoneid


# 終了
exit 0
