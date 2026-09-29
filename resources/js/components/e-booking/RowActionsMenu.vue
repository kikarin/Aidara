<script lang="ts">
import type { ConfirmOptions } from '@/composables/useConfirm';
import type { Component } from 'vue';

export type RowAction = {
    label: string;
    /** A Vue icon component, or a FontAwesome solid icon name (e.g. `'pen'`) already registered in the library. */
    icon?: Component | string;
    href?: string;
    onClick?: () => void;
    variant?: 'default' | 'destructive';
    confirm?: ConfirmOptions;
};
</script>

<script setup lang="ts">
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useConfirm } from '@/composables/useConfirm';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faEllipsis } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link } from '@inertiajs/vue3';

library.add(faEllipsis);

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
        ? 'gap-2.5 rounded-lg text-destructive focus:bg-destructive/10 focus:text-destructive'
        : 'gap-2.5 rounded-lg text-foreground';

const iconClass = (item: RowAction) => (item.variant === 'destructive' ? 'size-3.5 text-destructive' : 'size-3.5 text-muted-foreground');
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="text-muted-foreground hover:bg-muted hover:text-foreground data-[state=open]:bg-muted data-[state=open]:text-foreground focus-visible:ring-ring inline-flex size-8 items-center justify-center rounded-lg transition-colors focus-visible:ring-2 focus-visible:outline-none"
                aria-label="Aksi"
            >
                <FontAwesomeIcon :icon="['fas', 'ellipsis']" class="size-4" aria-hidden="true" />
            </button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-48 rounded-xl p-1.5">
            <template v-for="(item, index) in items" :key="index">
                <DropdownMenuItem v-if="item.href" as-child :class="itemClass(item)">
                    <Link :href="item.href" class="flex w-full items-center gap-2.5">
                        <template v-if="item.icon">
                            <FontAwesomeIcon
                                v-if="typeof item.icon === 'string'"
                                :icon="['fas', item.icon]"
                                :class="iconClass(item)"
                                aria-hidden="true"
                            />
                            <component :is="item.icon" v-else :class="iconClass(item)" aria-hidden="true" />
                        </template>
                        {{ item.label }}
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem v-else :class="itemClass(item)" @click="run(item)">
                    <template v-if="item.icon">
                        <FontAwesomeIcon
                            v-if="typeof item.icon === 'string'"
                            :icon="['fas', item.icon]"
                            :class="iconClass(item)"
                            aria-hidden="true"
                        />
                        <component :is="item.icon" v-else :class="iconClass(item)" aria-hidden="true" />
                    </template>
                    {{ item.label }}
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
