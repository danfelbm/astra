<?php

namespace Modules\Proyectos\Database\seeders;

use Illuminate\Database\Seeder;
use Modules\Proyectos\Models\CategoriaEtiqueta;
use Modules\Proyectos\Models\Etiqueta;

class EtiquetasSeeder extends Seeder
{
    /**
     * Ejecutar el seeder - crea categorías y etiquetas de ejemplo
     */
    public function run(): void
    {
        // Categoría: Estado
        $estado = CategoriaEtiqueta::create([
            'nombre' => 'Estado',
            'slug' => 'estado',
            'color' => 'blue',
            'icono' => 'Flag',
            'descripcion' => 'Estado del proyecto',
            'orden' => 1,
            'activo' => true
        ]);

        // Etiquetas de estado
        Etiqueta::create(['nombre' => 'Urgente', 'slug' => 'urgente', 'categoria_etiqueta_id' => $estado->id, 'color' => 'red']);
        Etiqueta::create(['nombre' => 'En revisión', 'slug' => 'en-revision', 'categoria_etiqueta_id' => $estado->id]);
        Etiqueta::create(['nombre' => 'Aprobado', 'slug' => 'aprobado', 'categoria_etiqueta_id' => $estado->id, 'color' => 'green']);

        // Categoría: Departamento
        $depto = CategoriaEtiqueta::create([
            'nombre' => 'Departamento',
            'slug' => 'departamento',
            'color' => 'purple',
            'icono' => 'Building',
            'descripcion' => 'Departamento responsable',
            'orden' => 2,
            'activo' => true
        ]);

        // Etiquetas de departamento
        Etiqueta::create(['nombre' => 'Tecnología', 'slug' => 'tecnologia', 'categoria_etiqueta_id' => $depto->id]);
        Etiqueta::create(['nombre' => 'Marketing', 'slug' => 'marketing', 'categoria_etiqueta_id' => $depto->id]);
        Etiqueta::create(['nombre' => 'Ventas', 'slug' => 'ventas', 'categoria_etiqueta_id' => $depto->id]);
        Etiqueta::create(['nombre' => 'Recursos Humanos', 'slug' => 'recursos-humanos', 'categoria_etiqueta_id' => $depto->id]);

        // Categoría: Tipo
        $tipo = CategoriaEtiqueta::create([
            'nombre' => 'Tipo',
            'slug' => 'tipo',
            'color' => 'green',
            'icono' => 'Tag',
            'descripcion' => 'Tipo de proyecto',
            'orden' => 3,
            'activo' => true
        ]);

        // Etiquetas de tipo
        Etiqueta::create(['nombre' => 'Desarrollo', 'slug' => 'desarrollo', 'categoria_etiqueta_id' => $tipo->id]);
        Etiqueta::create(['nombre' => 'Investigación', 'slug' => 'investigacion', 'categoria_etiqueta_id' => $tipo->id]);
        Etiqueta::create(['nombre' => 'Mantenimiento', 'slug' => 'mantenimiento', 'categoria_etiqueta_id' => $tipo->id]);
        Etiqueta::create(['nombre' => 'Mejora', 'slug' => 'mejora', 'categoria_etiqueta_id' => $tipo->id]);

        echo "✅ Categorías y etiquetas de ejemplo creadas exitosamente.\n";
    }
}