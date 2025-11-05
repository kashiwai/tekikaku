#!/bin/bash
# NET8 Auto-Approve Hooks Switcher
# このスクリプトは、NET8プロジェクトの完全自動承認モードを有効化します

set -e

# カラー定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# パス定義
HOOKS_CONFIG_DIR="$HOME/.config/claude-code"
NET8_HOOKS="$HOOKS_CONFIG_DIR/claude.hooks.net8.json"
ACTIVE_HOOKS="$HOOKS_CONFIG_DIR/claude.hooks.json"
BACKUP_HOOKS="$HOOKS_CONFIG_DIR/claude.hooks.backup.$(date +%Y%m%d_%H%M%S).json"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}🚀 NET8 Auto-Approve Mode Switcher${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# NET8専用hooks設定ファイルの存在確認
if [ ! -f "$NET8_HOOKS" ]; then
    echo -e "${RED}❌ Error: NET8 hooks configuration not found!${NC}"
    echo -e "${RED}   Expected: $NET8_HOOKS${NC}"
    exit 1
fi

echo -e "${GREEN}✅ NET8 hooks configuration found${NC}"
echo -e "   Path: $NET8_HOOKS"
echo ""

# 既存の設定をバックアップ
if [ -f "$ACTIVE_HOOKS" ]; then
    echo -e "${YELLOW}📦 Backing up current hooks configuration...${NC}"
    cp "$ACTIVE_HOOKS" "$BACKUP_HOOKS"
    echo -e "${GREEN}✅ Backup created: $BACKUP_HOOKS${NC}"
    echo ""
else
    echo -e "${YELLOW}ℹ️  No existing hooks configuration found (first-time setup)${NC}"
    echo ""
fi

# NET8設定を有効化
echo -e "${BLUE}🔧 Activating NET8 auto-approve hooks...${NC}"
cp "$NET8_HOOKS" "$ACTIVE_HOOKS"
echo -e "${GREEN}✅ Hooks activated!${NC}"
echo ""

# 設定内容の確認
echo -e "${BLUE}📋 Active hooks configuration:${NC}"
echo -e "${GREEN}   - Tool-use: Auto-approve${NC}"
echo -e "${GREEN}   - File-edit: Auto-approve${NC}"
echo -e "${GREEN}   - File-create: Auto-approve${NC}"
echo -e "${GREEN}   - File-delete: Auto-approve (CAUTION)${NC}"
echo -e "${GREEN}   - Bash-command: Auto-approve${NC}"
echo -e "${GREEN}   - Logging: Enabled${NC}"
echo ""

# ログディレクトリの確認
LOG_DIR="/Users/kotarokashiwai/net8_rebirth/net8/.claude/workspace/logs"
if [ ! -d "$LOG_DIR" ]; then
    echo -e "${YELLOW}⚠️  Log directory not found, creating...${NC}"
    mkdir -p "$LOG_DIR"
    echo -e "${GREEN}✅ Log directory created: $LOG_DIR${NC}"
else
    echo -e "${GREEN}✅ Log directory exists: $LOG_DIR${NC}"
fi
echo ""

# 完了メッセージ
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}🎉 Auto-Approve Mode Activated!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${BLUE}📝 Activity logs will be saved to:${NC}"
echo -e "   $LOG_DIR/activity.log"
echo -e "   $LOG_DIR/hooks.log"
echo ""
echo -e "${YELLOW}⚠️  WARNING:${NC}"
echo -e "${YELLOW}   All file operations will be automatically approved.${NC}"
echo -e "${YELLOW}   Review logs regularly for security.${NC}"
echo ""

# Claude Codeの再起動を推奨
echo -e "${BLUE}💡 Next Steps:${NC}"
echo -e "   1. Restart Claude Code to apply the new hooks configuration"
echo -e "   2. Check logs in .claude/workspace/logs/ to monitor activity"
echo -e "   3. To revert: cp $BACKUP_HOOKS $ACTIVE_HOOKS"
echo ""
