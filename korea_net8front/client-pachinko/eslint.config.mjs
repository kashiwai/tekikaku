import { dirname } from "path";
import { fileURLToPath } from "url";
import { FlatCompat } from "@eslint/eslintrc";
import js from "@eslint/js";
import typescriptPlugin from "@typescript-eslint/eslint-plugin";
import typescriptParser from "@typescript-eslint/parser";
import prettier from "eslint-config-prettier";
import importPlugin from "eslint-plugin-import";
import reactPlugin from "eslint-plugin-react";
import reactHooks from "eslint-plugin-react-hooks";
import jsxA11y from "eslint-plugin-jsx-a11y";

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const compat = new FlatCompat({ baseDirectory: __dirname });

const eslintConfig = []
// const eslintConfig = [
//   ...compat.config({
//     extends: ["next/core-web-vitals", "next", "next/typescript"],
//   }),

//   {
//     ignores: [
//       ".next/**",
//       "dist/**",
//       "build/**",
//       "node_modules/**",
//       "coverage/**",
//       "*.min.js",
//       "!.prettierrc.js",
//       "**/next-env.d.ts",
//     ],
//   },

//   js.configs.recommended,

//   {
//     files: ["**/*.{ts,tsx}"],
//     languageOptions: {
//       parser: typescriptParser,
//       parserOptions: {
//         ecmaVersion: "latest",
//         sourceType: "module",
//         ecmaFeatures: { jsx: true },
//         project: "./tsconfig.json",
//         tsconfigRootDir: __dirname,
//       },
//     },
//     plugins: { "@typescript-eslint": typescriptPlugin },
//     rules: {
//       "no-unused-vars": "off",
//       "@typescript-eslint/no-unused-vars": [
//         "error",
//         {
//           argsIgnorePattern: "^_",
//           varsIgnorePattern: "^_",
//           caughtErrorsIgnorePattern: "^_",
//           ignoreRestSiblings: true,
//         },
//       ],

//       "@typescript-eslint/no-explicit-any": "warn",
//       "@typescript-eslint/explicit-function-return-type": "off",
//       "@typescript-eslint/explicit-module-boundary-types": "off",
//       "@typescript-eslint/no-non-null-assertion": "warn",

//       "require-atomic-updates": "error",
//     },
//   },

//   {
//     files: ["**/*.{jsx,tsx}"],
//     plugins: {
//       react: reactPlugin,
//       "react-hooks": reactHooks,
//       "jsx-a11y": jsxA11y,
//     },
//     rules: {
//       "react/react-in-jsx-scope": "off",
//       "react/prop-types": "off",
//       "react/jsx-uses-vars": "error",
//       "react/jsx-no-target-blank": ["error", { enforceDynamicLinks: "always" }],

//       "react-hooks/rules-of-hooks": "error",
//       "react-hooks/exhaustive-deps": "warn",
//     },
//     settings: { react: { version: "detect" } },
//   },

//   {
//     files: ["**/*.{js,jsx,ts,tsx}"],
//     plugins: { import: importPlugin},
//     languageOptions: { ecmaVersion: "latest", sourceType: "module" },
//     settings: {
//       "import/resolver": {
//         typescript: {
//           project: "./tsconfig.json",
//         },
//         node: {
//           extensions: [".js", ".jsx", ".ts", ".tsx"],
//           paths: ["src"],
//         },
//         alias: {
//           map: [["@", "./src"]],
//           extensions: [".js", ".ts", ".tsx", ".jsx"],
//         },
//       },
//     },
//     rules: {
//       "import/no-unresolved": [
//         "off", // disables errors for wrong-case imports
//       ],
//       "import/named": "error",
//       "import/default": "error",
//       "import/namespace": "error",
//       "import/no-duplicates": "error",
//       "import/no-cycle": ["error", { maxDepth: 1 }],
//       "import/no-self-import": "error",
//       "import/order": [
//         "error",
//         {
//           groups: [
//             "object",
//             "builtin",
//             "external",
//             "internal",
//             ["parent", "sibling", "index"],
//             "type",
//           ],
//           pathGroups: [
//             {
//               pattern: "**/*.{css,scss,sass,less}",
//               group: "object",
//               position: "before",
//             },
//             { pattern: "react", group: "external", position: "before" },
//             { pattern: "next/**", group: "external", position: "before" },
//             // keep alias patterns minimal — the typescript resolver handles the heavy lifting
//             { pattern: "@/**", group: "internal", position: "after" },
//           ],
//           pathGroupsExcludedImportTypes: ["react", "next"],
//           "newlines-between": "always",
//           alphabetize: { order: "asc", caseInsensitive: true },
//         },
//       ],
//       "no-restricted-imports": [
//         "error",
//         {
//           patterns: ["../**"],
//         },
//       ],
//     },
//   },

//   {
//     files: ["**/*.{js,jsx,ts,tsx}"],
//     rules: {
//       "no-console": process.env.NODE_ENV === "production" ? "error" : "warn",
//       "no-debugger": process.env.NODE_ENV === "production" ? "error" : "warn",
//       "prefer-const": "error",
//       "no-var": "error",
//       "prefer-arrow-callback": "error",
//       "arrow-parens": ["error", "always"],
//       "no-duplicate-imports": "error",
//       "no-restricted-syntax": [
//         "error",
//         "WithStatement",
//         "BinaryExpression[operator='in']",
//       ],
//       "no-param-reassign": "error",
//       "object-shorthand": "error",
//       "prefer-template": "error",
//     },
//   },

//   {
//     files: ["**/*.{spec,test}.{ts,tsx,js,jsx}", "**/*.stories.{ts,tsx,js,jsx}"],
//     rules: {
//       "@typescript-eslint/no-unused-vars": [
//         "warn",
//         { argsIgnorePattern: "^_", varsIgnorePattern: "^_" },
//       ],
//       "no-console": "off",
//     },
//   },

//   prettier,
// ];

export default eslintConfig;


