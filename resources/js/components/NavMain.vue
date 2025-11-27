<script setup lang="ts">
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';

const props = defineProps<{
    items: NavItem[];
    sectionTitle?: string;
    sectionId?: string;
}>();

const getStorageKey = () => `openGroups_${props.sectionId || 'default'}`;
const openGroups = ref<string[]>(JSON.parse(localStorage.getItem(getStorageKey()) || '[]'));
const page = usePage();

const toggleGroup = (title: string) => {
    if (openGroups.value.includes(title)) {
        openGroups.value = openGroups.value.filter((t) => t !== title);
    } else {
        openGroups.value.push(title);
    }
    localStorage.setItem(getStorageKey(), JSON.stringify(openGroups.value));
};

const isActive = (href?: string) => {
    if (!href) return false;
    if (href === '/') return page.url === '/';
    return page.url === href || page.url.startsWith(href + '/');
};

const findActiveParents = (items: NavItem[], currentUrl: string): string[] => {
    const foundPath: string[] = [];

    const traverse = (items: NavItem[]): boolean => {
        for (const item of items) {
            if (item.href && currentUrl.startsWith(item.href)) {
                foundPath.push(item.title);
                return true;
            }
            if (item.children) {
                if (traverse(item.children)) {
                    foundPath.unshift(item.title);
                    return true;
                }
            }
        }
        return false;
    };

    traverse(items);
    foundPath.pop();
    return foundPath;
};

const openActiveMenus = () => {
    const activeParents = findActiveParents(props.items, page.url);
    const newOpenGroups = Array.from(new Set([...openGroups.value, ...activeParents]));
    openGroups.value = newOpenGroups;
    localStorage.setItem(getStorageKey(), JSON.stringify(newOpenGroups));
};

onMounted(() => {
    openActiveMenus();
});

watch(
    () => page.url,
    () => {
        openActiveMenus();
    },
);
</script>

<template>
    <div class="px-2 py-2">
        <p v-if="sectionTitle" class="text-muted-foreground mb-2 px-2 text-xs font-semibold tracking-wide uppercase">
            {{ sectionTitle }}
        </p>

        <ul class="space-y-1">
            <template v-for="item in items" :key="item.title">
                <!-- Dropdown -->
                <li v-if="item.children">
                    <button
                        @click="toggleGroup(item.title)"
                        class="hover:bg-muted flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition"
                    >
                        <span class="flex items-center gap-2">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </span>
                        <ChevronDown class="h-4 w-4 transition-transform" :class="{ 'rotate-180': openGroups.includes(item.title) }" />
                    </button>

                    <ul v-show="openGroups.includes(item.title)" class="border-border mt-1 ml-4 space-y-1 border-l pl-2">
                        <template v-for="child in item.children" :key="child.title">
                            <li v-if="child.children">
                                <button
                                    @click="toggleGroup(child.title)"
                                    class="hover:bg-muted flex w-full justify-between rounded px-3 py-1 text-sm"
                                >
                                    {{ child.title }}
                                    <ChevronDown class="h-4 w-4 transition-transform" :class="{ 'rotate-180': openGroups.includes(child.title) }" />
                                </button>
                                <ul v-show="openGroups.includes(child.title)" class="border-border/50 mt-1 ml-3 space-y-1 border-l pl-2">
                                    <li v-for="sub in child.children" :key="sub.title">
                                        <Link
                                            :href="sub.href"
                                            class="hover:bg-muted block rounded px-3 py-1 text-xs"
                                            :class="{ 'bg-muted font-semibold': isActive(sub.href) }"
                                        >
                                            {{ sub.title }}
                                        </Link>
                                    </li>
                                </ul>
                            </li>

                            <li v-else>
                                <Link
                                    :href="child.href"
                                    class="hover:bg-muted block rounded px-3 py-1 text-sm"
                                    :class="{ 'bg-muted font-semibold': isActive(child.href) }"
                                >
                                    {{ child.title }}
                                </Link>
                            </li>
                        </template>
                    </ul>
                </li>

                <!-- Normal link -->
                <li v-else>
                    <Link
                        :href="item.href"
                        class="hover:bg-muted flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition"
                        :class="{ 'bg-muted font-semibold': isActive(item.href) }"
                    >
                        <component :is="item.icon" class="h-4 w-4" />
                        {{ item.title }}
                    </Link>
                </li>
            </template>
        </ul>
    </div>
</template>
