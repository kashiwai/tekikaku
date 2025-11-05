#!/bin/bash
# Claude Code User Prompt Submit Hook
# AI運用5原則 対応版：計画承認後は自動実行

set -e

# 設定
AUTO_APPROVE_FLAG="/Users/kotarokashiwai/net8_rebirth/.claude/.auto_approve_mode"
PROJECT_ROOT="/Users/kotarokashiwai/net8_rebirth"

# 入力プロンプトを取得（環境変数から）
USER_PROMPT="${PROMPT:-}"

# デバッグログ（オプション）
# echo "[Hook] User prompt: $USER_PROMPT" >&2

# 自動承認モードがONの場合
if [ -f "$AUTO_APPROVE_FLAG" ]; then
    # y/n確認プロンプトが含まれている場合、自動でyを返す
    if echo "$USER_PROMPT" | grep -qiE "\(y/n\)|続行しますか|実行しますか|よろしいですか"; then
        echo "y"
        exit 0
    fi
fi

# 計画承認（/launch-task）でyが入力された場合、自動承認モードをON
if echo "$USER_PROMPT" | grep -qiE "^y+$"; then
    # 直前のメッセージに「この計画で続行しますか」が含まれているか確認
    # （簡易版：yが入力されたらフラグを立てる）
    touch "$AUTO_APPROVE_FLAG"
    echo "[Auto-Approve Mode] 有効化 - タスク完了まで自動実行します" >&2
fi

# タスク完了メッセージが含まれている場合、自動承認モードをOFF
if echo "$USER_PROMPT" | grep -qiE "タスク完了|作業完了|完了しました|finished|completed"; then
    if [ -f "$AUTO_APPROVE_FLAG" ]; then
        rm -f "$AUTO_APPROVE_FLAG"
        echo "[Auto-Approve Mode] 無効化 - タスク完了" >&2
    fi
fi

# プロンプトをそのまま返す（変更なし）
echo "$USER_PROMPT"
