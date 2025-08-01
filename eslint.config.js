import js from '@eslint/js';
import prettier from 'eslint-config-prettier';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import globals from 'globals';
import typescript from 'typescript-eslint';

/** @type {import('eslint').Linter.Config[]} */
export default [
    js.configs.recommended,
    ...typescript.configs.recommended,
    {
        ...react.configs.flat.recommended,
        ...react.configs.flat['jsx-runtime'], // Required for React 17+
        languageOptions: {
            globals: {
                ...globals.browser,
            },
        },
        rules: {
            'react/react-in-jsx-scope': 'off',
            'react/prop-types': 'off',
            'react/no-unescaped-entities': 'off',
        },
        settings: {
            react: {
                version: 'detect',
            },
        },
    },
    {
        plugins: {
            'react-hooks': reactHooks,
        },
        rules: {
            'react-hooks/rules-of-hooks': 'error',
            'react-hooks/exhaustive-deps': 'warn',
        },
    },
    {
        ignores: [
            'vendor', 
            'node_modules', 
            'public', 
            'bootstrap/ssr', 
            'tailwind.config.js',
            'resources/js/ziggy.js',     // Archivo generado por Ziggy
            'resources/js/ziggy*.js'     // Cualquier archivo generado por Ziggy en el futuro
        ],
    },
    prettier, // Turn off all rules that might conflict with Prettier
    // Special rule overrides for specific patterns
    {
        files: ['**/components/base-form/**/*.tsx'],
        rules: {
            '@typescript-eslint/no-explicit-any': 'off', // Disable any type warnings in form components
        },
    },
];
