/**
 * Keys para provide/inject del sistema de Tabs responsive
 */
import type { Component, ComputedRef, Ref } from 'vue'

// Key para el contexto de Tabs (valor actual)
export const TABS_CONTEXT_KEY = Symbol('tabs-context')

// Key para el contexto de TabsList (registro de tabs)
export const TABS_LIST_KEY = Symbol('tabs-list')

// Tipo para la info de cada tab
export interface TabInfo {
  value: string
  label: string
  icon?: Component
  iconHtml?: string // SVG extraído automáticamente del slot
  badge?: string | number
  disabled?: boolean
}

// Tipo para el contexto de Tabs
export interface TabsContext {
  modelValue: ComputedRef<string | undefined>
  updateValue: (value: string) => void
}

// Tipo para el contexto de TabsList
export interface TabsListContext {
  registerTab: (tab: TabInfo) => void
  unregisterTab: (value: string) => void
  isMobile: Ref<boolean>
}
