<template>
  <button
    class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold text-white
           bg-gradient-to-r from-purple-600 to-fuchsia-500
           shadow-md shadow-purple-200
           hover:from-purple-700 hover:to-fuchsia-600
           focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2
           active:scale-[0.99]
           disabled:opacity-50 disabled:cursor-not-allowed transition"
  >
    <slot />
  </button>
</template>
