import { computed, type ComputedRef } from 'vue';
import type { Hito } from '../types/hitos';

export interface HitoWithChildren extends Hito {
  children?: HitoWithChildren[];
  _nivel?: number;
}

export function useHitoHierarchy(hitos: ComputedRef<Hito[]> | Hito[]) {
  /**
   * Construye un árbol jerárquico de hitos
   */
  const buildTree = (parentId: number | null = null): HitoWithChildren[] => {
    const hitosArray = Array.isArray(hitos) ? hitos : hitos.value;

    return hitosArray
      .filter(h => h.parent_id === parentId)
      .map(hito => ({
        ...hito,
        children: buildTree(hito.id)
      }));
  };

  /**
   * Árbol jerárquico completo
   */
  const arbolHitos = computed(() => buildTree());

  /**
   * Lista plana con nivel de jerarquía
   */
  const hitosConNivel = computed(() => {
    const hitosArray = Array.isArray(hitos) ? hitos : hitos.value;
    const raices = hitosArray.filter(h => !h.parent_id);

    const buildFlat = (parentId: number | null, nivel: number = 0): HitoWithChildren[] => {
      return hitosArray
        .filter(h => h.parent_id === parentId)
        .flatMap(h => [
          { ...h, _nivel: nivel },
          ...buildFlat(h.id, nivel + 1)
        ]);
    };

    return raices.flatMap(raiz => [
      { ...raiz, _nivel: 0 },
      ...buildFlat(raiz.id, 1)
    ]);
  });

  /**
   * Obtiene todos los hijos (descendientes) de un hito
   */
  const getDescendientes = (hitoId: number): Hito[] => {
    const hitosArray = Array.isArray(hitos) ? hitos : hitos.value;
    const descendientes: Hito[] = [];

    const collect = (parentId: number) => {
      const hijos = hitosArray.filter(h => h.parent_id === parentId);
      hijos.forEach(hijo => {
        descendientes.push(hijo);
        collect(hijo.id);
      });
    };

    collect(hitoId);
    return descendientes;
  };

  /**
   * Obtiene todos los ancestros de un hito
   */
  const getAncestros = (hitoId: number): Hito[] => {
    const hitosArray = Array.isArray(hitos) ? hitos : hitos.value;
    const ancestros: Hito[] = [];
    let hito = hitosArray.find(h => h.id === hitoId);

    while (hito && hito.parent_id) {
      const padre = hitosArray.find(h => h.id === hito!.parent_id);
      if (padre) {
        ancestros.unshift(padre);
        hito = padre;
      } else {
        break;
      }
    }

    return ancestros;
  };

  /**
   * Verifica si un hito puede ser padre de otro (evita ciclos)
   */
  const puedeSerPadre = (hitoId: number, posiblePadreId: number): boolean => {
    if (hitoId === posiblePadreId) return false;

    const descendientes = getDescendientes(hitoId);
    return !descendientes.some(d => d.id === posiblePadreId);
  };

  /**
   * Obtiene hitos disponibles como padres (excluye el hito actual y sus descendientes)
   */
  const getHitosDisponiblesComoPadres = (hitoId: number | null = null): Hito[] => {
    const hitosArray = Array.isArray(hitos) ? hitos : hitos.value;

    if (!hitoId) {
      return hitosArray;
    }

    const descendientes = getDescendientes(hitoId);
    const idsExcluidos = [hitoId, ...descendientes.map(d => d.id)];

    return hitosArray.filter(h => !idsExcluidos.includes(h.id));
  };

  /**
   * Calcula el nivel de jerarquía de un hito
   */
  const getNivel = (hitoId: number): number => {
    const ancestros = getAncestros(hitoId);
    return ancestros.length;
  };

  /**
   * Formatea la ruta completa del hito (breadcrumb)
   */
  const getRutaCompleta = (hitoId: number): string => {
    const hitosArray = Array.isArray(hitos) ? hitos : hitos.value;
    const ancestros = getAncestros(hitoId);
    const hito = hitosArray.find(h => h.id === hitoId);

    if (!hito) return '';

    const ruta = [...ancestros.map(a => a.nombre), hito.nombre];
    return ruta.join(' / ');
  };

  /**
   * Obtiene solo los hitos raíz (sin padre)
   */
  const hitosRaiz = computed(() => {
    const hitosArray = Array.isArray(hitos) ? hitos : hitos.value;
    return hitosArray.filter(h => !h.parent_id);
  });

  /**
   * Cuenta total de hitos por nivel
   */
  const contarPorNivel = computed(() => {
    const conteo: Record<number, number> = {};

    hitosConNivel.value.forEach(h => {
      const nivel = h._nivel || 0;
      conteo[nivel] = (conteo[nivel] || 0) + 1;
    });

    return conteo;
  });

  return {
    // Computed
    arbolHitos,
    hitosConNivel,
    hitosRaiz,
    contarPorNivel,

    // Funciones
    getDescendientes,
    getAncestros,
    puedeSerPadre,
    getHitosDisponiblesComoPadres,
    getNivel,
    getRutaCompleta,
    buildTree,
  };
}
