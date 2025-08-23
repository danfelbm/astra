<template>
  <div
    :style="computedStyle"
    :class="computedClasses"
  />
</template>

<script setup lang="ts">
import { computed, type CSSProperties } from 'vue'
import { cn } from '@/lib/utils'

export interface GlowEffectProps {
  className?: string
  style?: CSSProperties
  mode?: 'animated' | 'static'
  intensity?: 'low' | 'medium' | 'high'
  duration?: number
}

const props = withDefaults(defineProps<GlowEffectProps>(), {
  mode: 'animated',
  intensity: 'medium',
  duration: 4
})

const getAnimationClass = (mode: typeof props.mode): string => {
  return mode === 'animated' ? 'rainbow-glow-animated' : ''
}

const computedStyle = computed(() => ({
  ...props.style,
  '--duration': `${props.duration}s`,
  willChange: 'transform, box-shadow'
} as CSSProperties))

const computedClasses = computed(() => 
  cn(
    'pointer-events-none absolute inset-0 rounded-[inherit] glow-effect',
    getAnimationClass(props.mode),
    props.className
  )
)
</script>

<style scoped>
/* Gradiente Blur de fondo (más sutil y detrás) */
.glow-effect::before {
  content: '';
  position: absolute;
  inset: -4px;
  background: linear-gradient(135deg, 
    #0894FF,
    #C959DD,
    #FF2E54,
    #FF9004,
    #0894FF
  );
  filter: blur(8px);
  opacity: 0.15;
  z-index: -10;
  border-radius: 1rem;
}

/* Animación del Box Shadow que cambia colores (más sutil) */
@keyframes rainbow-glow {
  0%, 100% {
    box-shadow: 
      0 0 15px 3px rgba(8, 148, 255, 0.2),
      0 0 25px 6px rgba(201, 89, 221, 0.15),
      0 0 35px 8px rgba(255, 46, 84, 0.1);
  }
  33% {
    box-shadow: 
      0 0 15px 3px rgba(255, 46, 84, 0.2),
      0 0 25px 6px rgba(255, 144, 4, 0.15),
      0 0 35px 8px rgba(8, 148, 255, 0.1);
  }
  66% {
    box-shadow: 
      0 0 15px 3px rgba(201, 89, 221, 0.2),
      0 0 25px 6px rgba(8, 148, 255, 0.15),
      0 0 35px 8px rgba(255, 144, 4, 0.1);
  }
}

/* Aplicar animación */
.rainbow-glow-animated {
  animation: rainbow-glow var(--duration) ease-in-out infinite;
}

/* Versión estática más sutil */
.glow-effect {
  box-shadow: 
    0 0 10px 2px rgba(8, 148, 255, 0.2),
    0 0 18px 4px rgba(201, 89, 221, 0.15),
    0 0 25px 6px rgba(255, 46, 84, 0.1),
    0 0 32px 8px rgba(255, 144, 4, 0.08);
}

/* Responder a prefer-reduced-motion */
@media (prefers-reduced-motion: reduce) {
  .rainbow-glow-animated {
    animation: none;
  }
  
  /* Mantener el gradiente blur pero sin animación */
  .glow-effect::before {
    filter: blur(20px);
    opacity: 0.5;
  }
}

/* Variaciones de intensidad */
.intensity-low::before {
  opacity: 0.4;
  filter: blur(20px);
}

.intensity-medium::before {
  opacity: 0.7;
  filter: blur(40px);
}

.intensity-high::before {
  opacity: 0.9;
  filter: blur(60px);
}
</style>