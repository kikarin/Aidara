import type { Ref, InjectionKey } from 'vue';

export interface TabsContext {
    selectedValue: Ref<string | undefined>;
    onValueChange: (value: string) => void;
    orientation?: 'horizontal' | 'vertical';
    activationMode?: 'automatic' | 'manual';
}

export const TABS_CONTEXT_KEY: InjectionKey<TabsContext> = Symbol('TabsContext'); 