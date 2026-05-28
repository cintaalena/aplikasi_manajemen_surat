<template>
  <button
    class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold text-white
           bg-green-700
           hover:bg-green-800
           focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2
           active:scale-[0.99]
           disabled:opacity-50 disabled:cursor-not-allowed transition"
  >
    <slot />
  </button>
</template>
