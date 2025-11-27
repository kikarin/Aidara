<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue';
import {
  DateFormatter,
  type DateValue,
  getLocalTimeZone,
  parseDate,
  today,
} from '@internationalized/date';
import { CalendarIcon, X } from 'lucide-vue-next';
import { cn } from '@/utils';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Input } from '@/components/ui/input';

const props = defineProps<{
  modelValue: string;
  placeholder?: string;
  required?: boolean;
  disabled?: boolean;
  variant?: 'default' | 'popover';
}>();

const emit = defineEmits(['update:modelValue']);

// Date formatter for display
const df = new DateFormatter('id-ID', {
  day: '2-digit',
  month: '2-digit',
  year: 'numeric',
});

// Internal state
const inputValue = ref('');
const dateValue = ref<DateValue | undefined>();
const isPopoverOpen = ref(false);
const inputRef = ref<HTMLInputElement>();

// Convert dd-mm-yyyy to DateValue
function ddmmyyyyToDateValue(dateStr: string): DateValue | undefined {
  if (!dateStr || !/^\d{2}-\d{2}-\d{4}$/.test(dateStr)) return undefined;
  
  const [dd, mm, yyyy] = dateStr.split('-');
  try {
    return parseDate(`${yyyy}-${mm}-${dd}`);
  } catch {
    return undefined;
  }
}

// Convert DateValue to dd-mm-yyyy
function dateValueToDdmmyyyy(date: DateValue): string {
  const jsDate = date.toDate(getLocalTimeZone());
  const dd = String(jsDate.getDate()).padStart(2, '0');
  const mm = String(jsDate.getMonth() + 1).padStart(2, '0');
  const yyyy = String(jsDate.getFullYear());
  return `${dd}-${mm}-${yyyy}`;
}

// Computed display value
const displayValue = computed(() => {
  if (dateValue.value) {
    return df.format(dateValue.value.toDate(getLocalTimeZone()));
  }
  return '';
});

// Initialize component
onMounted(() => {
  inputValue.value = props.modelValue || '';
  dateValue.value = ddmmyyyyToDateValue(props.modelValue);
});

// Watch for external changes
watch(() => props.modelValue, (val) => {
  inputValue.value = val || '';
  dateValue.value = ddmmyyyyToDateValue(val);
});

// Handle manual input
function handleInput(e: Event) {
  const val = (e.target as HTMLInputElement).value;
  inputValue.value = val;
  
  // Auto-format as user types
  let formattedVal = val.replace(/\D/g, ''); // Remove non-digits
  if (formattedVal.length >= 2) {
    formattedVal = formattedVal.substring(0, 2) + '-' + formattedVal.substring(2);
  }
  if (formattedVal.length >= 5) {
    formattedVal = formattedVal.substring(0, 5) + '-' + formattedVal.substring(5, 9);
  }
  
  if (formattedVal !== val) {
    inputValue.value = formattedVal;
    if (inputRef.value) {
      inputRef.value.value = formattedVal;
    }
  }
  
  // Validate and emit if complete
  if (/^\d{2}-\d{2}-\d{4}$/.test(formattedVal)) {
    const newDateValue = ddmmyyyyToDateValue(formattedVal);
    if (newDateValue) {
      dateValue.value = newDateValue;
      emit('update:modelValue', formattedVal);
    }
  }
}

// Handle calendar selection
function handleCalendarSelect(date: DateValue | undefined) {
  dateValue.value = date;
  if (date) {
    const ddmmyyyy = dateValueToDdmmyyyy(date);
    inputValue.value = ddmmyyyy;
    emit('update:modelValue', ddmmyyyy);
  } else {
    inputValue.value = '';
    emit('update:modelValue', '');
  }
  isPopoverOpen.value = false;
}

// Handle native date input
function handleDateChange(e: Event) {
  const val = (e.target as HTMLInputElement).value;
  if (val) {
    const [yyyy, mm, dd] = val.split('-');
    const ddmmyyyy = `${dd}-${mm}-${yyyy}`;
    inputValue.value = ddmmyyyy;
    dateValue.value = ddmmyyyyToDateValue(ddmmyyyy);
    emit('update:modelValue', ddmmyyyy);
  }
}

// Clear date
function clearDate() {
  dateValue.value = undefined;
  inputValue.value = '';
  emit('update:modelValue', '');
}

// Get today's date in dd-mm-yyyy format
function getTodayDdmmyyyy(): string {
  const todayDate = today(getLocalTimeZone());
  return dateValueToDdmmyyyy(todayDate);
}

// Set today's date
function setToday() {
  const todayStr = getTodayDdmmyyyy();
  const todayDate = ddmmyyyyToDateValue(todayStr);
  dateValue.value = todayDate;
  inputValue.value = todayStr;
  emit('update:modelValue', todayStr);
  isPopoverOpen.value = false;
}
</script>

<template>
  <div class="space-y-2">
    <!-- Popover variant (default) -->
    <Popover v-if="variant !== 'default'" v-model:open="isPopoverOpen">
      <PopoverTrigger as-child>
        <Button
          variant="outline"
          :class="cn(
            'w-full justify-start text-left font-normal',
            !dateValue && 'text-muted-foreground',
            disabled && 'cursor-not-allowed opacity-50'
          )"
          :disabled="disabled"
        >
          <CalendarIcon class="mr-2 h-4 w-4" />
          {{ displayValue || placeholder || "Pilih tanggal" }}
          <X 
            v-if="dateValue && !disabled" 
            class="ml-auto h-4 w-4 hover:text-destructive" 
            @click.stop="clearDate"
          />
        </Button>
      </PopoverTrigger>
      <PopoverContent class="w-auto p-0" align="start">
        <div class="p-3 border-b">
          <div class="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              @click="setToday"
              class="text-xs"
            >
              Hari ini
            </Button>
            <Button
              variant="ghost"
              size="sm"
              @click="clearDate"
              class="text-xs text-muted-foreground"
            >
              Hapus
            </Button>
          </div>
        </div>
        <Calendar 
          v-model="dateValue" 
          initial-focus 
          @update:model-value="handleCalendarSelect"
        />
      </PopoverContent>
    </Popover>

    <!-- Input variant -->
    <div v-else class="relative flex items-center">
      <Input
        ref="inputRef"
        :value="inputValue"
        @input="handleInput"
        :placeholder="placeholder || 'dd-mm-yyyy'"
        :required="required"
        :disabled="disabled"
        class="pr-20"
        pattern="\d{2}-\d{2}-\d{4}"
        maxlength="10"
      />
      
      <!-- Clear button -->
      <Button
        v-if="inputValue && !disabled"
        variant="ghost"
        size="sm"
        class="absolute right-10 h-6 w-6 p-0 hover:bg-transparent"
        @click="clearDate"
        tabindex="-1"
      >
        <X class="h-3 w-3" />
      </Button>
      
      <!-- Native date input (hidden) -->
      <input
        type="date"
        class="absolute right-2 top-1/2 -translate-y-1/2 opacity-0 w-6 h-6 cursor-pointer"
        style="z-index: 2;"
        @change="handleDateChange"
        tabindex="-1"
        :disabled="disabled"
      />
      
      <!-- Calendar icon -->
      <CalendarIcon 
        class="absolute right-2 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" 
      />
    </div>

    <!-- Display selected date -->
    <p v-if="dateValue" class="text-xs text-muted-foreground">
      Tanggal terpilih: {{ displayValue }}
    </p>
  </div>
</template>