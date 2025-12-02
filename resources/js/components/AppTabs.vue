<script setup lang="ts">
import Tabs from '../components/ui/tabs/Tabs.vue';
import TabsList from '../components/ui/tabs/TabsList.vue';
import TabsTrigger from '../components/ui/tabs/TabsTrigger.vue';

const props = defineProps<{
    tabs: {
        value: string;
        label: string;
        component?: any;
        props?: Record<string, any>;
        disabled?: boolean;
        onSave?: (form: any, setFormErrors: (errors: Record<string, string>) => void) => Promise<any>;
        isRedirectTab?: boolean;
        onClick?: () => void;
    }[];
    defaultValue: string;
    modelValue?: string;
}>();

const emit = defineEmits(['update:modelValue', 'tab-change']);

const handleTabChange = (tabValue: string) => {
    const tab = props.tabs.find((t) => t.value === tabValue);
    if (tab?.disabled) return;

    if (tab?.isRedirectTab && tab.onClick) {
        tab.onClick();
    } else {
        emit('update:modelValue', tabValue);
        emit('tab-change', tabValue);
    }
};
</script>

<template>
    <Tabs :model-value="props.modelValue || props.defaultValue" @update:model-value="handleTabChange" class="w-full">
        <TabsList class="bg-muted inline-flex max-w-full overflow-x-auto rounded-md border">
            <TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value" :disabled="tab.disabled" class="flex-shrink-0">
                {{ tab.label }}
            </TabsTrigger>
        </TabsList>
    </Tabs>
</template>
