<script setup>
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */

    // Skeleton loader component that is API compatible (subset) with the Skeleton component from PrimeVue
    defineProps({
        shape: {
            type: String,
            default: 'rectangle',
            validator: (value) => ['rectangle', 'circle'].includes(value),
        },
        size: {
            type: String,
            default: null,
        },
        width: {
            type: String,
            default: '100%',
        },
        height: {
            type: String,
            default: '1rem',
        },
        borderRadius: {
            type: String,
            default: null,
        },
        animation: {
            type: String,
            default: 'wave',
            validator: (value) => ['wave', 'none'].includes(value),
        },
    });

    const dark_mode = document.documentElement.getAttribute('data-glpi-theme-dark') === '1';
    const bg_color = dark_mode ? '#ffffff0f' : 'var(--tblr-secondary-bg)';
</script>

<template>
    <div
        class="skeleton"
        :class="[
            `skeleton-${shape}`,
            animation !== 'none' ? `skeleton-${animation}` : '',
        ]"
        :style="{
            width: size || width,
            height: size || height,
            borderRadius: borderRadius,
        }"
    ></div>
</template>

<style scoped>
    .skeleton {
        background-color: v-bind(bg_color);
        position: relative;
        overflow: hidden;
    }

    .skeleton-rectangle {
        border-radius: 0.25rem;
    }

    .skeleton-circle {
        border-radius: 50%;
    }

    .skeleton-wave::after {
        content: '';
        position: absolute;
        top: 0;
        left: -150%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        animation: wave 1.5s infinite;
    }

    @keyframes wave {
        to {
            left: 150%;
        }
    }
</style>
