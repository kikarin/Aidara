<script lang="ts">
import type { ConfirmOptions } from '@/composables/useConfirm';
import type { Component } from 'vue';

export type RowAction = {
    label: string;
    icon?: Component;
    href?: string;
    onClick?: () => void;
    variant?: 'default' | 'destructive';
    confirm?: ConfirmOptions;
};
</script>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useConfirm } from '@/composables/useConfirm';
import { Link } from '@inertiajs/vue3';
import { MoreVertical } from 'lucide-vue-next';

defineProps<{
    items: RowAction[];
}>();

const { confirm } = useConfirm();

const run = async (item: RowAction) => {
    if (!item.onClick) return;

    if (item.confirm) {
        const ok = await confirm(item.confirm);
        if (!ok) return;
    }

    item.onClick();
};

const itemClass = (item: RowAction) =>
    item.variant === 'destructive'
        ? 'gap-2 text-red-600 focus:bg-red-50 focus:text-red-700 dark:text-red-400 dark:focus:bg-red-950/40 dark:focus:text-red-300'
        : 'gap-2';
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon-sm" class="data-[state=open]:bg-accent" aria-label="Aksi">
                <MoreVertical class="size-4" />
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-44">
            <template v-for="(item, index) in items" :key="index">
                <DropdownMenuItem v-if="item.href" as-child :class="itemClass(item)">
                    <Link :href="item.href" class="flex w-full items-center gap-2">
                        <component
                            :is="item.icon"
                            v-if="item.icon"
                            class="size-4"
                            :class="item.variant === 'destructive' ? 'text-red-600 dark:text-red-400' : ''"
                        />
                        {{ item.label }}
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem v-else :class="itemClass(item)" @click="run(item)">
                    <component
                        :is="item.icon"
                        v-if="item.icon"
                        class="size-4"
                        :class="item.variant === 'destructive' ? 'text-red-600 dark:text-red-400' : ''"
                    />
                    {{ item.label }}
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
