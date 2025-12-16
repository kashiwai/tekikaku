// components/forms/auth/unifiedLoginForm.tsx
import { useState, useEffect } from "react";

import { zodResolver } from "@hookform/resolvers/zod";
import { useTranslations } from "next-intl";
import { useForm } from "react-hook-form";

import { unifiedLogin } from "@/actions/unified-auth.actions";
import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { toastSuccess, toastDanger } from "@/components/ui/sonner";
import { useModal } from "@/hooks/useModal";
import { useUnifiedAuth } from "@/hooks/useUnifiedAuth";
import { AuthModalTab } from "@/types/modal.types";
import { AuthSchemas, authSchemas } from "@/validations/auth.schemas";
import { useAsync } from "@/hooks/useAsync";
import { useFormStore } from "@/store/form.store";
import { useUserStore } from "@/store/user.store";

type Props = {
  setActiveTab: (val: AuthModalTab) => void;
  onLoginSuccess?: () => void;
};

export default function UnifiedLoginForm({ setActiveTab, onLoginSuccess }: Props) {
  const t = useTranslations("LOGIN_FORM");
  const loginModal = useModal("auth");
  
  const { loading, startLoading, stopLoading } = useFormStore();
  const setUser = useUserStore((store) => store.setUser);
  const { syncKoreaUser, loading: authLoading, error: authError } = useUnifiedAuth();

  const [syncStatus, setSyncStatus] = useState<'idle' | 'syncing' | 'synced' | 'failed'>('idle');

  const form = useForm<AuthSchemas["koreaLogin"]>({
    resolver: zodResolver(authSchemas.koreaLogin),
    defaultValues: {
      loginId: "",
      password: "",
    },
  });

  async function onSubmit(values: AuthSchemas["koreaLogin"]) {
    startLoading();
    setSyncStatus('idle');

    try {
      // 1. 統合ログイン実行
      const res = await unifiedLogin(values, Intl.DateTimeFormat().resolvedOptions().timeZone);
      
      if (!res.success) {
        form.setError("root", { message: res.error || "ログインに失敗しました" });
        stopLoading();
        return;
      }

      if (res.koreaUser) {
        // 2. 韓国側ログイン成功 - ユーザー情報設定
        setUser(res.koreaUser);
        
        // 3. NET8自動同期開始
        setSyncStatus('syncing');
        
        const syncResult = await syncKoreaUser(res.koreaUser);
        
        if (syncResult.success) {
          setSyncStatus('synced');
          toastSuccess("ログインに成功しました。NET8パチンコ・スロットもご利用いただけます。");
          
          loginModal.onClose();
          form.reset();
          onLoginSuccess?.();
          
        } else if (syncResult.needsRegistration) {
          setSyncStatus('failed');
          toastDanger("NET8連携に失敗しました。再試行してください。");
          
        } else {
          setSyncStatus('failed');
          toastDanger(`NET8同期エラー: ${syncResult.error}`);
        }
      }

    } catch (error) {
      console.error("Login error:", error);
      form.setError("root", { 
        message: error instanceof Error ? error.message : "予期しないエラーが発生しました" 
      });
      setSyncStatus('failed');
    } finally {
      stopLoading();
    }
  }

  const isFormLoading = loading || authLoading || syncStatus === 'syncing';

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
        <FormField
          control={form.control}
          name="loginId"
          render={({ field }) => (
            <FormItem>
              <FormLabel>ログインID</FormLabel>
              <FormControl>
                <Input
                  placeholder="testuser1またはメールアドレス"
                  {...field}
                  disabled={isFormLoading}
                  type="text"
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        <FormField
          control={form.control}
          name="password"
          render={({ field }) => (
            <FormItem>
              <FormLabel>{t("PASSWORD_LABEL")}</FormLabel>
              <FormControl>
                <Input
                  placeholder={t("PASSWORD_PLACEHOLDER")}
                  {...field}
                  disabled={isFormLoading}
                  type="password"
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        {form.formState.errors.root && (
          <div className="text-sm text-red-600 dark:text-red-400">
            {form.formState.errors.root.message}
          </div>
        )}

        {authError && (
          <div className="text-sm text-red-600 dark:text-red-400">
            NET8同期エラー: {authError}
          </div>
        )}

        {/* 同期ステータス表示 */}
        {syncStatus === 'syncing' && (
          <div className="flex items-center space-x-2 text-sm text-blue-600">
            <div className="animate-spin h-4 w-4 border-2 border-blue-600 border-t-transparent rounded-full"></div>
            <span>NET8パチンコ・スロットアカウントを連携中...</span>
          </div>
        )}

        {syncStatus === 'synced' && (
          <div className="text-sm text-green-600 dark:text-green-400">
            ✅ NET8パチンコ・スロット連携完了
          </div>
        )}

        {syncStatus === 'failed' && (
          <div className="text-sm text-orange-600 dark:text-orange-400">
            ⚠️ NET8連携に失敗しましたが、通常のサービスはご利用いただけます
          </div>
        )}

        <Button type="submit" className="w-full" disabled={isFormLoading}>
          {isFormLoading ? (
            <div className="flex items-center space-x-2">
              <div className="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></div>
              <span>
                {syncStatus === 'syncing' ? 'NET8連携中...' : 'ログイン中...'}
              </span>
            </div>
          ) : (
            t("LOGIN_BUTTON")
          )}
        </Button>

        <div className="flex justify-center">
          <Button
            variant="default"
            onClick={() => setActiveTab("register")}
            type="button"
            disabled={isFormLoading}
          >
            {t("REGISTER_LINK")}
          </Button>
        </div>
      </form>
    </Form>
  );
}