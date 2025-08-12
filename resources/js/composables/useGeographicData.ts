import { ref, watch } from 'vue';
import type { 
    Territorio, 
    Departamento, 
    Municipio, 
    Localidad, 
    GeographicRestrictions 
} from '@/types/forms';

export function useGeographicData() {
    // Geographic data
    const territorios = ref<Territorio[]>([]);
    const departamentos = ref<Departamento[]>([]);
    const municipios = ref<Municipio[]>([]);
    const localidades = ref<Localidad[]>([]);
    
    // All geographic data (for pre-loading)
    const allDepartamentos = ref<Departamento[]>([]);
    const allMunicipios = ref<Municipio[]>([]);
    const allLocalidades = ref<Localidad[]>([]);
    
    const loading = ref(false);

    // Load territorios
    const loadTerritorios = async () => {
        try {
            loading.value = true;
            const response = await fetch('/admin/geographic/territorios');
            territorios.value = await response.json();
        } catch (error) {
            console.error('Error loading territorios:', error);
        } finally {
            loading.value = false;
        }
    };

    // Load departamentos
    const loadDepartamentos = async (territorioIds?: number[]) => {
        try {
            loading.value = true;
            const url = territorioIds && territorioIds.length > 0
                ? `/admin/geographic/departamentos?territorio_ids=${territorioIds.join(',')}`
                : '/admin/geographic/departamentos';
            const response = await fetch(url);
            const data = await response.json();
            
            if (territorioIds && territorioIds.length > 0) {
                departamentos.value = data;
            } else {
                allDepartamentos.value = data;
            }
        } catch (error) {
            console.error('Error loading departamentos:', error);
        } finally {
            loading.value = false;
        }
    };

    // Load municipios
    const loadMunicipios = async (departamentoIds?: number[]) => {
        try {
            loading.value = true;
            const url = departamentoIds && departamentoIds.length > 0
                ? `/admin/geographic/municipios?departamento_ids=${departamentoIds.join(',')}`
                : '/admin/geographic/municipios';
            const response = await fetch(url);
            const data = await response.json();
            
            if (departamentoIds && departamentoIds.length > 0) {
                municipios.value = data;
            } else {
                allMunicipios.value = data;
            }
        } catch (error) {
            console.error('Error loading municipios:', error);
        } finally {
            loading.value = false;
        }
    };

    // Load localidades
    const loadLocalidades = async (municipioIds?: number[]) => {
        try {
            loading.value = true;
            const url = municipioIds && municipioIds.length > 0
                ? `/admin/geographic/localidades?municipio_ids=${municipioIds.join(',')}`
                : '/admin/geographic/localidades';
            const response = await fetch(url);
            const data = await response.json();
            
            if (municipioIds && municipioIds.length > 0) {
                localidades.value = data;
            } else {
                allLocalidades.value = data;
            }
        } catch (error) {
            console.error('Error loading localidades:', error);
        } finally {
            loading.value = false;
        }
    };

    // Setup cascade watchers
    const setupCascadeWatchers = (restrictions: GeographicRestrictions) => {
        // Watch territorios changes
        watch(() => restrictions.territorios_ids, async (newTerritorios) => {
            restrictions.departamentos_ids = [];
            restrictions.municipios_ids = [];
            restrictions.localidades_ids = [];
            departamentos.value = [];
            municipios.value = [];
            localidades.value = [];
            
            if (newTerritorios && newTerritorios.length > 0) {
                await loadDepartamentos(newTerritorios);
            }
        });

        // Watch departamentos changes
        watch(() => restrictions.departamentos_ids, async (newDepartamentos) => {
            restrictions.municipios_ids = [];
            restrictions.localidades_ids = [];
            municipios.value = [];
            localidades.value = [];
            
            if (newDepartamentos && newDepartamentos.length > 0) {
                await loadMunicipios(newDepartamentos);
            }
        });

        // Watch municipios changes
        watch(() => restrictions.municipios_ids, async (newMunicipios) => {
            restrictions.localidades_ids = [];
            localidades.value = [];
            
            if (newMunicipios && newMunicipios.length > 0) {
                await loadLocalidades(newMunicipios);
            }
        });
    };

    // Initialize geographic data for editing mode
    const initializeForEditing = async (restrictions: GeographicRestrictions) => {
        await loadTerritorios();
        
        if (restrictions.territorios_ids?.length) {
            await loadDepartamentos(restrictions.territorios_ids);
        }
        if (restrictions.departamentos_ids?.length) {
            await loadMunicipios(restrictions.departamentos_ids);
        }
        if (restrictions.municipios_ids?.length) {
            await loadLocalidades(restrictions.municipios_ids);
        }
    };

    // Reset all geographic selections
    const resetGeographicData = () => {
        departamentos.value = [];
        municipios.value = [];
        localidades.value = [];
    };

    return {
        // State
        territorios,
        departamentos,
        municipios,
        localidades,
        allDepartamentos,
        allMunicipios,
        allLocalidades,
        loading,
        
        // Actions
        loadTerritorios,
        loadDepartamentos,
        loadMunicipios,
        loadLocalidades,
        setupCascadeWatchers,
        initializeForEditing,
        resetGeographicData,
    };
}