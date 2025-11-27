<script setup lang="ts">
import { provide, ref } from 'vue';
import { TABS_CONTEXT_KEY } from './tabs-context';

const props = defineProps<{
    modelValue?: string;
    defaultValue?: string;
    orientation?: 'horizontal' | 'vertical';
    dir?: 'ltr' | 'rtl';
    activationMode?: 'automatic' | 'manual';
}>();

const emit = defineEmits(['update:modelValue']);

const localValue = ref(props.modelValue || props.defaultValue);

provide(TABS_CONTEXT_KEY, {
    selectedValue: localValue,
    onValueChange: (value: string) => {
        localValue.value = value;
        emit('update:modelValue', value);
    },
    orientation: props.orientation,
    activationMode: props.activationMode,
});
</script>

<template>
    <div :data-orientation="orientation">
        <slot />
    </div>
</template> 