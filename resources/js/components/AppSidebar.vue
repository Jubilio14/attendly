<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, 
    FolderGit2, 
    LayoutGrid,
    Users,
    ClipboardCheck,
    FileText,
    BarChart3,
    CalendarClock,
    Building2} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
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
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';


const mainNavItems = [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Karyawan',
        href: '/admin/employees',
        icon: Users,
    },
    {
        title: 'Absensi',
        href: '/admin/attendances',
        icon: ClipboardCheck,
    },
    {
        title: 'Requests',
        href: '/admin/requests',
        icon: FileText,
    },
    {
        title: 'Laporan',
        href: '/admin/reports',
        icon: BarChart3,
    },
    {
        title: 'Jadwal Kerja',
        href: '/admin/work-schedules',
        icon: CalendarClock,
    },
    {
        title: 'Departemen',
        href: '/admin/departments',
        icon: Building2,
    },
];

const footerNavItems = [];
</script>

<template>
    <Sidebar collapsible="icon">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
