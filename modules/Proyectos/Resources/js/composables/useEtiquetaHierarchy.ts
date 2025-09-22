import { ref, computed, Ref } from 'vue';
import type { Etiqueta } from '../types/etiquetas';

export function useEtiquetaHierarchy(etiquetas: Ref<Etiqueta[]> = ref([])) {
    /**
     * Construye un árbol jerárquico desde una lista plana de etiquetas
     */
    const construirArbol = (etiquetasPlanas: Etiqueta[]): Etiqueta[] => {
        const mapa = new Map<number, Etiqueta>();
        const raices: Etiqueta[] = [];

        // Crear un mapa de etiquetas por ID
        etiquetasPlanas.forEach(etiqueta => {
            mapa.set(etiqueta.id, { ...etiqueta, children: [] });
        });

        // Construir el árbol
        etiquetasPlanas.forEach(etiqueta => {
            const nodo = mapa.get(etiqueta.id);
            if (!nodo) return;

            if (etiqueta.parent_id) {
                const padre = mapa.get(etiqueta.parent_id);
                if (padre) {
                    if (!padre.children) padre.children = [];
                    padre.children.push(nodo);
                }
            } else {
                raices.push(nodo);
            }
        });

        return raices;
    };

    /**
     * Aplana un árbol jerárquico en una lista plana
     */
    const aplanarArbol = (nodos: Etiqueta[]): Etiqueta[] => {
        const resultado: Etiqueta[] = [];

        const aplanarRecursivo = (nodo: Etiqueta) => {
            resultado.push(nodo);
            if (nodo.children && nodo.children.length > 0) {
                nodo.children.forEach(hijo => aplanarRecursivo(hijo));
            }
        };

        nodos.forEach(nodo => aplanarRecursivo(nodo));
        return resultado;
    };

    /**
     * Busca una etiqueta por ID en el árbol
     */
    const buscarEnArbol = (id: number, nodos: Etiqueta[] = etiquetas.value): Etiqueta | null => {
        for (const nodo of nodos) {
            if (nodo.id === id) return nodo;

            if (nodo.children && nodo.children.length > 0) {
                const encontrado = buscarEnArbol(id, nodo.children);
                if (encontrado) return encontrado;
            }
        }
        return null;
    };

    /**
     * Obtiene el camino desde la raíz hasta una etiqueta
     */
    const obtenerCamino = (etiquetaId: number): Etiqueta[] => {
        const camino: Etiqueta[] = [];
        const etiquetasPlanas = aplanarArbol(etiquetas.value);

        let actual = etiquetasPlanas.find(e => e.id === etiquetaId);

        while (actual) {
            camino.unshift(actual);
            actual = actual.parent_id
                ? etiquetasPlanas.find(e => e.id === actual!.parent_id)
                : null;
        }

        return camino;
    };

    /**
     * Verifica si una etiqueta es descendiente de otra
     */
    const esDescendienteDe = (etiquetaId: number, posibleAncestroId: number): boolean => {
        const camino = obtenerCamino(etiquetaId);
        return camino.some(e => e.id === posibleAncestroId);
    };

    /**
     * Valida si se puede establecer una relación padre-hijo sin crear ciclos
     */
    const validarRelacionPadreHijo = (hijoId: number, padreId: number): boolean => {
        // No puede ser su propio padre
        if (hijoId === padreId) return false;

        // El padre no puede ser descendiente del hijo
        return !esDescendienteDe(padreId, hijoId);
    };

    /**
     * Cuenta el total de nodos en el árbol
     */
    const contarNodos = (nodos: Etiqueta[] = etiquetas.value): number => {
        let total = 0;

        const contarRecursivo = (nodo: Etiqueta) => {
            total++;
            if (nodo.children) {
                nodo.children.forEach(hijo => contarRecursivo(hijo));
            }
        };

        nodos.forEach(nodo => contarRecursivo(nodo));
        return total;
    };

    /**
     * Obtiene la profundidad máxima del árbol
     */
    const obtenerProfundidadMaxima = (nodos: Etiqueta[] = etiquetas.value): number => {
        if (!nodos || nodos.length === 0) return 0;

        const calcularProfundidad = (nodo: Etiqueta, nivel: number = 0): number => {
            if (!nodo.children || nodo.children.length === 0) return nivel;

            return Math.max(...nodo.children.map(hijo =>
                calcularProfundidad(hijo, nivel + 1)
            ));
        };

        return Math.max(...nodos.map(nodo => calcularProfundidad(nodo, 0)));
    };

    /**
     * Filtra el árbol por término de búsqueda
     */
    const filtrarArbol = (termino: string, nodos: Etiqueta[] = etiquetas.value): Etiqueta[] => {
        if (!termino) return nodos;

        const terminoLower = termino.toLowerCase();

        const filtrarRecursivo = (nodo: Etiqueta): Etiqueta | null => {
            const coincideNodo = nodo.nombre.toLowerCase().includes(terminoLower) ||
                                 (nodo.descripcion && nodo.descripcion.toLowerCase().includes(terminoLower));

            let hijosFilfiltrados: Etiqueta[] = [];

            if (nodo.children) {
                hijosFilfiltrados = nodo.children
                    .map(hijo => filtrarRecursivo(hijo))
                    .filter(hijo => hijo !== null) as Etiqueta[];
            }

            // Incluir si el nodo coincide o tiene hijos que coinciden
            if (coincideNodo || hijosFilfiltrados.length > 0) {
                return {
                    ...nodo,
                    children: hijosFilfiltrados
                };
            }

            return null;
        };

        return nodos
            .map(nodo => filtrarRecursivo(nodo))
            .filter(nodo => nodo !== null) as Etiqueta[];
    };

    /**
     * Helpers para drag & drop
     */
    const dragDropHelpers = {
        draggedItem: ref<Etiqueta | null>(null),
        dragOverItem: ref<Etiqueta | null>(null),

        handleDragStart(etiqueta: Etiqueta) {
            this.draggedItem.value = etiqueta;
        },

        handleDragOver(e: DragEvent, etiqueta: Etiqueta) {
            e.preventDefault();
            this.dragOverItem.value = etiqueta;
        },

        handleDrop(e: DragEvent, nuevoPadre: Etiqueta | null) {
            e.preventDefault();

            if (!this.draggedItem.value) return;

            const hijoId = this.draggedItem.value.id;
            const padreId = nuevoPadre?.id || null;

            // Validar que no se cree un ciclo
            if (padreId && !validarRelacionPadreHijo(hijoId, padreId)) {
                console.error('No se puede crear un ciclo en la jerarquía');
                return false;
            }

            // Aquí se debe llamar al API para actualizar la relación
            return {
                hijoId,
                padreId,
                valido: true
            };
        },

        handleDragEnd() {
            this.draggedItem.value = null;
            this.dragOverItem.value = null;
        }
    };

    /**
     * Expande/colapsa nodos del árbol
     */
    const expandidos = ref<Set<number>>(new Set());

    const toggleExpansion = (etiquetaId: number) => {
        if (expandidos.value.has(etiquetaId)) {
            expandidos.value.delete(etiquetaId);
        } else {
            expandidos.value.add(etiquetaId);
        }
    };

    const expandirTodos = () => {
        const todas = aplanarArbol(etiquetas.value);
        todas.forEach(e => {
            if (e.children && e.children.length > 0) {
                expandidos.value.add(e.id);
            }
        });
    };

    const colapsarTodos = () => {
        expandidos.value.clear();
    };

    /**
     * Computed para estadísticas del árbol
     */
    const estadisticas = computed(() => ({
        totalNodos: contarNodos(),
        profundidadMaxima: obtenerProfundidadMaxima(),
        totalRaices: etiquetas.value.filter(e => !e.parent_id).length,
        totalConHijos: aplanarArbol(etiquetas.value).filter(e => e.children && e.children.length > 0).length
    }));

    return {
        // Datos
        etiquetas,
        expandidos,
        estadisticas,

        // Funciones de construcción
        construirArbol,
        aplanarArbol,

        // Funciones de búsqueda
        buscarEnArbol,
        obtenerCamino,
        filtrarArbol,

        // Funciones de validación
        esDescendienteDe,
        validarRelacionPadreHijo,

        // Funciones de utilidad
        contarNodos,
        obtenerProfundidadMaxima,

        // Control de expansión
        toggleExpansion,
        expandirTodos,
        colapsarTodos,

        // Drag & Drop
        dragDropHelpers
    };
}