<script setup>
defineProps({
  modelValue: String,
});

defineEmits(['update:modelValue']);
</script>

<template>
  <input
    class="rounded-xl border-gray-200 shadow-sm w-full
           focus:border-purple-400 focus:ring-purple-400
           hover:border-purple-200
           transition"
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)"
  />
</template>
