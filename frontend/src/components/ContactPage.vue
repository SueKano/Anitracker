<script setup lang="ts">
import { ChevronLeft, ArrowUpRight } from 'lucide-vue-next'

const emit = defineEmits<{ back: [] }>()

const EMAIL = 'suekanoo@gmail.com'
const PAYPAL_URL = 'https://www.paypal.me/jorgesinmas'
</script>

<template>
  <div class="contact">
    <button class="contact-back" aria-label="Volver" @click="emit('back')">
      <ChevronLeft :stroke-width="2.2" />
    </button>

    <article class="contact-article">
      <h1 class="contact-title">
        <span class="contact-title-line">Contacto</span>
      </h1>
      <div class="contact-rule" aria-hidden="true">
        <span class="contact-rule-dot"/>
      </div>
      <p class="contact-lede">
        Si has encontrado un fallo, tienes alguna sugerencia o propuesta, puedes escribirme al siguiente correo
      </p>
      <div class="contact-channels">
        <a class="contact-card" :href="`mailto:${EMAIL}`">
          <span class="contact-card-mark">@</span>
          <div class="contact-card-body">
            <span class="contact-card-value">{{ EMAIL }}</span>
          </div>
          <ArrowUpRight class="contact-card-arrow" :stroke-width="1.6" />
        </a>
      </div>
      <p class="contact-lede">
        Contestare a tu correo lo antes posible según mi disponibilidad
      </p>
      <div class="contact-rule contact-rule--soft" aria-hidden="true"/>
      <section class="contact-postscript">
        <h2 class="contact-ps-title">Apoya el proyecto</h2>
        <p class="contact-ps-body">
          Si te ha sido útil y quieres invitarme a un café, puedes hacerlo aqui.
        </p>
        <a class="contact-paypal" :href="PAYPAL_URL" target="_blank" rel="noopener">
          <span class="contact-paypal-text">
            <span class="contact-paypal-label">Donar con PayPal</span>
          </span>
          <span class="contact-paypal-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.106zm14.146-14.42a3.35 3.35 0 0 0-.607-.541c-.013.076-.026.175-.041.254-.59 3.025-2.566 5.595-7.39 5.595h-2.19c-.524 0-.968.382-1.05.9l-1.342 8.51a.642.642 0 0 0 .633.736h4.606c.456 0 .846-.334.918-.785l.04-.213.755-4.78.05-.27a.929.929 0 0 1 .918-.787h.58c3.748 0 6.682-1.526 7.538-5.937.358-1.846.174-3.388-.776-4.476z"/>
            </svg>
          </span>
        </a>
      </section>
    </article>
  </div>
</template>

<style scoped>
@import '../styles/ContactPage.css';
</style>