import { useState, useCallback } from "react";

type AsyncFunction<TArgs extends any[], TResult> = (...args: TArgs) => Promise<TResult>;

export function useAsync<TArgs extends any[], TResult>(
  asyncFn: AsyncFunction<TArgs, TResult>,
  options?: { onError?: (error: any) => void; onSuccess?: (result: TResult) => void }
) {
  const [loading, setLoading] = useState(false);

  const run = useCallback(
    async (...args: TArgs) => {
      try {
        setLoading(true);
        const result = await asyncFn(...args);
        options?.onSuccess?.(result);
        return result;
      } catch (error) {
        options?.onError?.(error);
        throw error;
      } finally {
        setLoading(false);
      }
    },
    [asyncFn, options]
  );

  return { run, loading };
}