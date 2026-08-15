<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { dashboard, login, register } from '@/routes';
import { form as verifyForm } from '@/routes/certificates/verify';
import { index as coursesIndex } from '@/routes/courses';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Menu, Moon, Search, Sun, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

interface Props {
    appName?: string;
    canRegister?: boolean;
}

withDefaults(defineProps<Props>(), {
    appName: 'EnterLMS',
    canRegister: true,
});

const page = usePage();
const { appearance, updateAppearance } = useAppearance();

const user = computed(() => page.props.auth?.user);
const isLearner = computed(() => user.value?.role === 'learner');
const currentUrl = computed(() => page.url.split('?')[0] ?? page.url);
const mobileMenuOpen = ref(false);
const searchQuery = ref('');

const catalogUrl = computed(() => coursesIndex().url);
const verifyUrl = computed(() => verifyForm().url);
const appHomeUrl = computed(() =>
    isLearner.value ? '/learner/dashboard' : dashboard().url,
);

const catalogActive = computed(() => currentUrl.value.startsWith('/courses'));
const verifyActive = computed(() => currentUrl.value.startsWith('/certificates/verify'));

const navLinkClass = (active: boolean) =>
    [
        'text-sm transition-colors',
        active
            ? 'font-medium text-foreground'
            : 'text-muted-foreground hover:text-foreground',
    ].join(' ');

const getInitials = (name: string) =>
    name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

const toggleTheme = () => {
    updateAppearance(appearance.value === 'dark' ? 'light' : 'dark');
};

const submitSearch = () => {
    const q = searchQuery.value.trim();
    if (!q) {
        return;
    }

    mobileMenuOpen.value = false;
    router.get(catalogUrl.value, { search: q }, { preserveState: false });
};
</script>

<template>
    <header class="sticky top-0 z-50 w-full border-b bg-surface/95 backdrop-blur supports-[backdrop-filter]:bg-surface/60">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center gap-6">
                <Link href="/" class="flex shrink-0 items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary">
                        <AppLogoIcon class="h-5 w-5 fill-current text-primary-foreground" />
                    </div>
                    <span class="font-display hidden text-xl font-normal tracking-tight text-foreground sm:block">
                        {{ appName }}
                    </span>
                </Link>

                <!-- Catalog chrome only. App destinations live in the sidebar. -->
                <nav class="hidden items-center gap-6 lg:flex" aria-label="Katalog">
                    <Link
                        v-if="user"
                        :href="catalogUrl"
                        :class="navLinkClass(catalogActive)"
                        :aria-current="catalogActive ? 'page' : undefined"
                    >
                        Katalog
                    </Link>
                    <Link
                        :href="verifyUrl"
                        :class="navLinkClass(verifyActive)"
                        :aria-current="verifyActive ? 'page' : undefined"
                    >
                        Verifikasi sertifikat
                    </Link>
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    <form
                        v-if="user"
                        class="relative hidden w-56 xl:block"
                        @submit.prevent="submitSearch"
                    >
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Cari kursus…"
                            class="h-9 w-full rounded-full border bg-surface-2/50 pr-4 pl-9 text-sm outline-none transition-colors focus:border-primary focus:bg-surface"
                        />
                    </form>

                    <Button
                        variant="ghost"
                        size="icon"
                        class="hidden sm:flex"
                        :aria-label="appearance === 'dark' ? 'Tema terang' : 'Tema gelap'"
                        @click="toggleTheme"
                    >
                        <Sun v-if="appearance === 'dark'" class="h-5 w-5" />
                        <Moon v-else class="h-5 w-5" />
                    </Button>

                    <template v-if="user">
                        <NotificationBell />
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="ghost" class="relative h-9 w-9 rounded-full">
                                    <Avatar class="h-9 w-9">
                                        <AvatarImage
                                            v-if="user.avatar"
                                            :src="user.avatar"
                                            :alt="user.name"
                                        />
                                        <AvatarFallback class="bg-primary/10 text-primary">
                                            {{ getInitials(user.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-56" align="end">
                                <div class="flex items-center justify-start gap-2 p-2">
                                    <div class="flex flex-col space-y-0.5 leading-none">
                                        <p class="text-sm font-medium">{{ user.name }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ user.email }}
                                        </p>
                                    </div>
                                </div>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem as-child>
                                    <Link :href="appHomeUrl">Dashboard</Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link href="/settings/profile">Pengaturan</Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem as-child>
                                    <Link href="/logout" method="post" as="button" class="w-full">
                                        Keluar
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </template>

                    <template v-else>
                        <Link :href="login()" class="hidden sm:block">
                            <Button variant="ghost">Masuk</Button>
                        </Link>
                        <Link v-if="canRegister" :href="register()" class="hidden sm:block">
                            <Button>Daftar</Button>
                        </Link>
                    </template>

                    <Button
                        variant="ghost"
                        size="icon"
                        class="lg:hidden"
                        :aria-label="mobileMenuOpen ? 'Tutup menu' : 'Buka menu'"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                    >
                        <X v-if="mobileMenuOpen" class="h-6 w-6" />
                        <Menu v-else class="h-6 w-6" />
                    </Button>
                </div>
            </div>
        </div>

        <div v-if="mobileMenuOpen" class="border-t lg:hidden">
            <div class="space-y-1 px-4 py-4">
                <form
                    v-if="user"
                    class="relative mb-4"
                    @submit.prevent="submitSearch"
                >
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="searchQuery"
                        type="search"
                        placeholder="Cari kursus…"
                        class="h-10 w-full rounded-lg border bg-surface-2/50 pr-4 pl-10 text-sm outline-none"
                    />
                </form>

                <Link
                    v-if="user"
                    :href="catalogUrl"
                    class="block rounded-lg px-3 py-2 text-base text-muted-foreground hover:bg-surface-2 hover:text-foreground"
                    @click="mobileMenuOpen = false"
                >
                    Katalog
                </Link>
                <Link
                    :href="verifyUrl"
                    class="block rounded-lg px-3 py-2 text-base text-muted-foreground hover:bg-surface-2 hover:text-foreground"
                    @click="mobileMenuOpen = false"
                >
                    Verifikasi sertifikat
                </Link>

                <div class="border-t pt-4">
                    <template v-if="user">
                        <Link
                            :href="appHomeUrl"
                            class="block rounded-lg px-3 py-2 text-base text-muted-foreground hover:bg-surface-2 hover:text-foreground"
                            @click="mobileMenuOpen = false"
                        >
                            Dashboard
                        </Link>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="block w-full rounded-lg px-3 py-2 text-left text-base text-muted-foreground hover:bg-surface-2 hover:text-foreground"
                        >
                            Keluar
                        </Link>
                    </template>
                    <template v-else>
                        <div class="flex gap-2">
                            <Link :href="login()" class="flex-1">
                                <Button variant="outline" class="w-full">Masuk</Button>
                            </Link>
                            <Link v-if="canRegister" :href="register()" class="flex-1">
                                <Button class="w-full">Daftar</Button>
                            </Link>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </header>
</template>
