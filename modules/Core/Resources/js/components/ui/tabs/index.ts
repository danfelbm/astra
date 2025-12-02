export { default as Tabs } from "./Tabs.vue"
export { default as TabsContent } from "./TabsContent.vue"
export { default as TabsList } from "./TabsList.vue"
export { default as TabsTrigger } from "./TabsTrigger.vue"

// Re-export keys y tipos para uso externo
export {
  TABS_CONTEXT_KEY,
  TABS_LIST_KEY,
  type TabInfo,
  type TabsContext,
  type TabsListContext
} from "./keys"
