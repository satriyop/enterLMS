import prettier from 'eslint-config-prettier/flat';
import vue from 'eslint-plugin-vue';

import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript';

export default defineConfigWithVueTs(
    vue.configs['flat/essential'],
    vueTsConfigs.recommended,
    {
        ignores: ['vendor', 'node_modules', 'public', 'bootstrap/ssr', 'tailwind.config.js', 'resources/js/components/ui/*'],
    },
    {
        rules: {
            'vue/multi-word-component-names': 'off',
            '@typescript-eslint/no-explicit-any': 'off',
            // Inertia's <Link> is a thin wrapper around <a> — v-html is safe
            'vue/no-v-text-v-html-on-component': ['error', { allow: ['Link'] }],
            // Allow unused args prefixed with _ (common convention)
            '@typescript-eslint/no-unused-vars': [
                'error',
                { argsIgnorePattern: '^_' },
            ],
        },
    },
    // Vue 3 <script setup> — `props` from defineProps is used in templates
    {
        files: ['**/*.vue'],
        rules: {
            '@typescript-eslint/no-unused-vars': [
                'error',
                { varsIgnorePattern: '^props$', argsIgnorePattern: '^_' },
            ],
        },
    },
    prettier,
);
