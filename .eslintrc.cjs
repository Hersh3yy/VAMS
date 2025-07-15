/* eslint-env node */
require("@rushstack/eslint-patch/modern-module-resolution")

module.exports = {
    root: true,
    extends: [
        "plugin:vue/vue3-essential",
        "eslint:recommended",
        "@vue/eslint-config-typescript",
        "@vue/eslint-config-prettier"
    ],
    plugins: [
        "unused-imports"
    ],
    parserOptions: {
        ecmaVersion: "latest"
    },
    rules: {
        // Existing rules
        "vue/multi-word-component-names": "off",
        "no-undef": "off",
        
        // Unused code detection
        "@typescript-eslint/no-unused-vars": ["error", { 
            "argsIgnorePattern": "^_",
            "varsIgnorePattern": "^_",
            "ignoreRestSiblings": true
        }],
        "no-unused-vars": "off", // Turn off base rule as it can report incorrect errors
        
        // Import organization
        "unused-imports/no-unused-imports": "error",
        "unused-imports/no-unused-vars": ["error", { 
            "vars": "all", 
            "varsIgnorePattern": "^_", 
            "args": "after-used", 
            "argsIgnorePattern": "^_" 
        }],
        
        // Vue specific rules
        "vue/no-mutating-props": "error",
        "vue/no-unused-vars": "error",
        "vue/no-unused-components": "error",
        "vue/require-default-prop": "warn",
        "vue/require-prop-types": "warn",
        "vue/prop-name-casing": ["error", "camelCase"],
        "vue/component-name-in-template-casing": ["error", "PascalCase"],
        "vue/html-self-closing": ["error", {
            "html": {
                "void": "never",
                "normal": "always",
                "component": "always"
            }
        }],
        
        // Code quality
        "no-console": ["warn", { "allow": ["warn", "error"] }],
        "no-debugger": "error",
        "prefer-const": "error",
        "no-var": "error",
        "object-shorthand": "error",
        "prefer-arrow-callback": "error",
        "arrow-spacing": "error",
        "comma-dangle": ["error", "never"],
        "quotes": ["error", "single"],
        "semi": ["error", "always"],
        
        // TypeScript specific
        "@typescript-eslint/explicit-function-return-type": "off",
        "@typescript-eslint/no-explicit-any": "warn",
        "@typescript-eslint/prefer-nullish-coalescing": "error",
        "@typescript-eslint/prefer-optional-chain": "error",
        
        // Ignore ziggy.js file issues
        "no-useless-escape": "off"
    },
    overrides: [
        {
            files: ["**/ziggy.js"],
            rules: {
                "no-useless-escape": "off",
                "@typescript-eslint/no-unused-vars": "off",
                "unused-imports/no-unused-imports": "off",
                "unused-imports/no-unused-vars": "off"
            }
        }
    ]
}
