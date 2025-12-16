export async function copyOnClipboard(
    text: string,
    setCopied?: (val: boolean) => void
  ) {
    try {
      await navigator.clipboard.writeText(text);
  
      if (setCopied) {
        setCopied(true);
        setTimeout(() => setCopied(false), 1500);
      }
    } catch (error: unknown) {
      if (error instanceof Error) {
        alert(`Can't copy: ${error.message}`);
      } else {
        alert("Can't copy: unknown error");
      }
    }
  }