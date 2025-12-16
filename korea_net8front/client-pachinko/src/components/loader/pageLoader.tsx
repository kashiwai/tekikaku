"use client";

import { useEffect, useState } from "react";

import { motion, AnimatePresence } from "framer-motion";

export default function PageLoader() {
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const handleLoad = () => {
      setTimeout(() => {
        setLoading(false);
      }, 200);
    };

    if (document.readyState === "complete") {
      handleLoad();
    } else {
      window.addEventListener("load", handleLoad);
    }

    return () => {
      window.removeEventListener("load", handleLoad);
    };
  }, []);

  return (
    <AnimatePresence>
      {loading && (
        <motion.div
          className="fixed flex top-0 left-0 z-[999] w-full h-svh page-loader bg-background"
          initial={{ opacity: 1 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0, transition: { duration: 0.5 } }}
        >
          <div
            className="w-[50px] h-[50px] rounded-full border-4 border-neutral/10 border-t-neutral m-auto will-change-transform animate-spin"

          />
        </motion.div>
      )}
    </AnimatePresence>
  );
}