<script setup lang="ts">
import Navbar from '@/components/home/Navbar.vue';
import Footer from '@/components/home/Footer.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    canRegister?: boolean;
}

withDefaults(defineProps<Props>(), {
    canRegister: true,
});

const page = usePage();
const appName = computed(() => page.props.name || 'E-Learning');
</script>

<template>
    <div class="min-h-screen">
        <Navbar :app-name="appName" :can-register="canRegister" />

        <slot />

        <Footer :app-name="appName" />
    </div>
</template>
