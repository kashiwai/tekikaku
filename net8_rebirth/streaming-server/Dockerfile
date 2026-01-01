FROM antmediaserver/antmediaserver:latest

# Community Edition設定
ENV SERVER_MODE=community

# WebRTC用ポート範囲
ENV RTMP_PORT=1935
ENV HTTP_PORT=5080
ENV HTTPS_PORT=5443

# データ永続化用ディレクトリ
VOLUME ["/usr/local/antmedia/webapps"]

# ポート公開
# 5080: HTTP管理画面 & REST API
# 5443: HTTPS (WebRTC signaling)
# 1935: RTMP入力
# 5000-5100: WebRTC UDP (ICE候補)
EXPOSE 5080 5443 1935 5000-5100/udp

# ヘルスチェック
HEALTHCHECK --interval=30s --timeout=10s --start-period=120s --retries=3 \
  CMD curl -f http://localhost:5080/LiveApp || exit 1

# デフォルトコマンド（Ant Media Server起動）
CMD ["/usr/local/antmedia/start.sh"]
