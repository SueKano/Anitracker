<script setup lang="ts">
const emit = defineEmits<{ back: [], contact: [] }>()

const lastUpdated = '26 de mayo de 2026'

const sections = [
  {
    number: '01',
    title: 'Quién es el responsable',
    body: 'El responsable del tratamiento de tus datos es Jorge Hernández, persona física que desarrolla este proyecto a título personal. Puedes contactarnos en suekanoo@gmail.com para cualquier cuestión relacionada con tus datos.',
  },
  {
    number: '02',
    title: 'Datos que recopilamos',
    body: 'Para que la aplicación funcione guardamos tu nombre de usuario, tu email, la foto de perfil que subas y la lista de series que sigues. Si te registras con email y contraseña, almacenamos una versión cifrada de tu contraseña. Si inicias sesión con Google, guardamos además un identificador interno que Google nos proporciona para reconocerte en futuros accesos. El servidor también registra de forma automática datos técnicos básicos de cada visita (dirección IP, fecha y navegador) que se conservan un máximo de 30 días para detectar abusos y mantener la seguridad.',
  },
  {
    number: '03',
    title: 'Inicio de sesión con Google',
    body: 'Si eliges entrar con Google, te redirigimos a su pantalla de consentimiento y solo solicitamos los permisos básicos (openid, email y profile). De toda esa información únicamente conservamos tu email y el identificador único que Google nos devuelve. No accedemos a tus contactos, archivos ni a ningún otro dato de tu cuenta de Google, y nunca recibimos tu contraseña.',
  },
  {
    number: '04',
    title: 'Para qué los usamos y con qué base legal',
    body: 'Usamos tus datos para identificar tu cuenta, mantener tu sesión y mostrarte tu propio progreso. La base legal es la ejecución del servicio que solicitas al registrarte (art. 6.1.b RGPD). La imagen de perfil se trata con tu consentimiento al subirla (art. 6.1.a RGPD) y los registros mínimos de seguridad técnica con interés legítimo (art. 6.1.f RGPD). No analizamos tu actividad para perfilarte, no vendemos datos y no los compartimos con anunciantes.',
  },
  {
    number: '05',
    title: 'Servicios que nos ayudan',
    body: 'Para hacer funcionar la aplicación nos apoyamos en proveedores que actúan como encargados de tratamiento: Hetzner Online GmbH (Alemania) aloja el servidor y la base de datos; Cloudflare, Inc. (EEUU) almacena las imágenes de perfil en R2 y enruta el tráfico; Google LLC (EEUU) actúa como proveedor de identidad si eliges iniciar sesión con Google. Todos ellos tratan los datos siguiendo nuestras instrucciones y bajo contratos que cumplen con el RGPD.',
  },
  {
    number: '06',
    title: 'Cómo viajan tus datos',
    body: 'Cloudflare y Google están establecidos en EEUU. Las transferencias internacionales de datos se amparan en el Marco de Privacidad de Datos UE-EEUU (Data Privacy Framework) y, cuando aplica, en las Cláusulas Contractuales Tipo aprobadas por la Comisión Europea. Hetzner aloja los datos exclusivamente en centros dentro del Espacio Económico Europeo.',
  },
  {
    number: '07',
    title: 'Cuánto los conservamos',
    body: 'Conservamos tus datos mientras tu cuenta esté activa. Cuando borras tu cuenta, eliminamos de forma inmediata tu usuario, tu email, tu imagen de perfil, tu identificador de Google si lo hubiera y todas tus series asociadas. Las copias de seguridad técnicas se rotan y se eliminan en un plazo máximo de 30 días.',
  },
  {
    number: '08',
    title: 'Menores de edad',
    body: 'Si eres menor de 14 años, necesitas el permiso de tus padres o tutores para crear una cuenta. Si descubrimos que se ha registrado una persona menor de esa edad sin ese consentimiento, eliminaremos la cuenta. Este límite lo marca la legislación española (LOPDGDD); no es una decisión nuestra.',
  },
  {
    number: '09',
    title: 'Cookies y almacenamiento técnico',
    body: 'Usamos una única cookie de sesión, estrictamente necesaria para mantenerte identificado mientras navegas. No empleamos cookies analíticas, de publicidad ni de seguimiento de terceros, por lo que no necesitamos pedirte consentimiento adicional para ellas.',
  },
  {
    number: '10',
    title: 'Tus derechos',
    body: 'Puedes acceder, rectificar y suprimir tus datos editando tu perfil o borrando tu cuenta desde los ajustes en cualquier momento. Si tu cuenta tiene contraseña, también puedes cambiarla desde ahí. Si entraste con Google, puedes además revocar el acceso de la aplicación desde myaccount.google.com en la sección de aplicaciones conectadas. Si necesitas ejercer cualquier otro derecho (oposición, limitación, portabilidad), escríbenos desde la página de contacto.',
  },
  {
    number: '11',
    title: 'Reclamaciones',
    body: 'Tienes derecho a presentar una reclamación ante la Agencia Española de Protección de Datos (www.aepd.es) si consideras que el tratamiento de tus datos no se ajusta a la normativa. Antes te agradeceríamos que nos contactes para tratar de resolverlo directamente.',
  },
  {
    number: '12',
    title: 'Contacto',
    body: 'Si tienes dudas sobre cómo tratamos tus datos o quieres ejercer cualquiera de tus derechos, puedes escribirnos desde la página de contacto y te responderemos lo antes posible.',
    action: 'contact' as const,
  }
]
</script>

<template>
  <div class="policy">
    <button class="policy-back" aria-label="Volver" @click="emit('back')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M15 18l-6-6 6-6"/>
      </svg>
    </button>

    <article class="policy-article">
      <h1 class="policy-title">
        <span class="policy-title-line">Política</span>
        <span class="policy-title-line policy-title-italic">de privacidad</span>
      </h1>
      <div class="policy-rule" aria-hidden="true">
        <span class="policy-rule-dot"/>
      </div>
      <section v-for="(section, index) in sections" :key="section.number" class="policy-section" :style="{ animationDelay: 80 + index * 60 + 'ms' }">
        <div class="policy-section-head">
          <span class="policy-num">{{ section.number }}</span>
          <h2 class="policy-section-title">{{ section.title }}</h2>
        </div>
        <p class="policy-body">{{ section.body }}</p>
        <button v-if="section.action === 'contact'" type="button" class="policy-link" @click="emit('contact')">
          Ir a la página de contacto
        </button>
      </section>
      <p class="policy-updated">Última actualización: {{ lastUpdated }}</p>
    </article>
  </div>
</template>

<style scoped>
@import '../styles/PrivacyPolicyPage.css';
</style>