<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { index as usersIndex } from '@/actions/App/Http/Controllers/Admin/UserController';
import { index as auditReportsIndex } from '@/actions/App/Http/Controllers/ComplianceReportController';
import MyLearningController from '@/actions/App/Http/Controllers/MyLearningController';
import { dashboard } from '@/routes';
import { index as coursesIndex } from '@/routes/courses';
import { index as learningPathsIndex } from '@/routes/learning-paths';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Award,
    BookOpen,
    FileText,
    GraduationCap,
    HelpCircle,
    LayoutGrid,
    Map,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const role = computed(() => user.value?.role ?? 'learner');
const isLearner = computed(() => role.value === 'learner');
const isLmsAdmin = computed(() => role.value === 'lms_admin');
const canManageCourses = computed(() => role.value === 'lms_admin');
const canViewCompliance = computed(() => role.value === 'lms_admin');

/** Claude B — guided groups: Belajar / Mengelola / Administrasi */
const learnNavItems = computed<NavItem[]>(() => {
    if (isLearner.value) {
        return [
            {
                title: 'Dashboard',
                href: '/learner/dashboard',
                icon: LayoutGrid,
            },
            {
                title: 'Cari kursus',
                href: coursesIndex(),
                icon: BookOpen,
            },
            {
                title: 'Pembelajaran saya',
                href: MyLearningController().url,
                icon: GraduationCap,
            },
            {
                title: 'Jalur pembelajaran',
                href: '/learner/learning-paths/browse',
                icon: Map,
            },
            {
                title: 'Sertifikat',
                href: '/certificates',
                icon: Award,
            },
        ];
    }

    return [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Kursus',
            href: coursesIndex(),
            icon: BookOpen,
        },
        {
            title: 'Jalur pembelajaran',
            href: learningPathsIndex(),
            icon: Map,
        },
    ];
});

const manageNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];
    if (canManageCourses.value) {
        items.push({
            title: 'Kelola kursus',
            href: coursesIndex(),
            icon: BookOpen,
        });
    }
    return items;
});

const adminNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];
    if (isLmsAdmin.value) {
        items.push({
            title: 'Manajemen pengguna',
            href: usersIndex().url,
            icon: Users,
        });
    }
    if (canViewCompliance.value) {
        items.push({
            title: 'Jejak aktivitas',
            href: auditReportsIndex().url,
            icon: FileText,
        });
    }
    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="isLearner ? '/learner/dashboard' : dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="learnNavItems" label="Belajar" />
            <NavMain
                v-if="manageNavItems.length > 0"
                :items="manageNavItems"
                label="Mengelola"
            />
            <NavMain
                v-if="adminNavItems.length > 0"
                :items="adminNavItems"
                label="Administrasi"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
