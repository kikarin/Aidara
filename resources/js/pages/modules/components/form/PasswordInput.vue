<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Eye, EyeOff } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    modelValue: string;
    placeholder?: string;
    required?: boolean;
}>();
const emit = defineEmits(['update:modelValue']);

const showPassword = ref(false);
</script>

<template>
    <div class="relative">
        <Input
            :type="showPassword ? 'text' : 'password'"
            :placeholder="placeholder"
            :required="required"
            :value="modelValue"
            @input="emit('update:modelValue', $event.target.value)"
        />
        <Button type="button" variant="ghost" size="sm" class="absolute top-1/2 right-2 -translate-y-1/2" @click="showPassword = !showPassword">
            <Eye v-if="!showPassword" class="h-4 w-4" />
            <EyeOff v-else class="h-4 w-4" />
        </Button>
    </div>
</template>
