<script setup lang="ts">
import HomePage from './components/HomePage.vue'
import { useToast } from './composables/useToast'

const { toasts } = useToast()
</script>

<template>
  <HomePage />
  <div class="toast-container">
    <div v-for="t in toasts" :key="t.id" class="toast" :class="`toast--${t.type}`">
      {{ t.message }}
    </div>
  </div>
</template>

<style>
@import './styles/Toast.css';
</style>