<script setup>
defineProps({
  modelValue: String,
});

defineEmits(['update:modelValue']);
</script>

<template>
  <input
    class="rounded-xl border-gray-200 shadow-sm w-full
           focus:border-green-500 focus:ring-green-500
           hover:border-green-300
           transition"
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)"
  />
</template>
