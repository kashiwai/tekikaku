@echo off
chcp 65001 > nul
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo 🚀 Railway セットアップ自動化スクリプト
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo.
echo このスクリプトは8つのセットアップURLを順番に開きます
echo 各URLでセットアップが完了したら、Enterキーを押して次へ進んでください
echo.
pause
echo.

echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo ✅ STEP 1: データベース接続確認
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
start https://mgg-webservice-production.up.railway.app/test_db_connection.php
echo ブラウザで接続確認を行ってください
echo 完了したらEnterを押してください...
pause > nul
echo.

echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo ✅ STEP 2: データベーステーブル作成
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
start https://mgg-webservice-production.up.railway.app/setup_database.php
echo ブラウザでテーブル作成を確認してください
echo 完了したらEnterを押してください...
pause > nul
echo.

echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo ✅ STEP 3: サンプル会員データ投入
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
start https://mgg-webservice-production.up.railway.app/data/xxxadmin/insert_sample_members.php
echo ブラウザで会員データ投入を確認してください
echo 完了したらEnterを押してください...
pause > nul
echo.

echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo ✅ STEP 4: MACアドレス登録
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
start https://mgg-webservice-production.up.railway.app/insert_mac_addresses.php
echo ブラウザでMACアドレス登録を確認してください
echo 完了したらEnterを押してください...
pause > nul
echo.

echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo ✅ STEP 5: カテゴリ更新
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
start https://mgg-webservice-production.up.railway.app/update_category.php
echo ブラウザでカテゴリ更新を確認してください
echo 完了したらEnterを押してください...
pause > nul
echo.

echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo ✅ STEP 6: 北斗モデル登録
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
start https://mgg-webservice-production.up.railway.app/insert_hokuto_model.php
echo ブラウザで北斗モデル登録を確認してください
echo 完了したらEnterを押してください...
pause > nul
echo.

echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo ✅ STEP 7: コーナー登録
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
start https://mgg-webservice-production.up.railway.app/register_corner.php
echo ブラウザでコーナー登録を確認してください
echo 完了したらEnterを押してください...
pause > nul
echo.

echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo ✅ STEP 8: カメラ登録
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
start https://mgg-webservice-production.up.railway.app/register_camera.php
echo ブラウザでカメラ登録を確認してください
echo 完了したらEnterを押してください...
pause > nul
echo.

echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo 🎉 セットアップ完了！
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo.
echo 次のURLでトップページを確認してください：
echo https://mgg-webservice-production.up.railway.app/
echo.
start https://mgg-webservice-production.up.railway.app/
echo.
pause
