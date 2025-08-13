<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Departamento;
use App\Models\Localidad;
use App\Models\Municipio;
use App\Models\Territorio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileLocationController extends Controller
{
    /**
     * Actualizar la información de ubicación del usuario.
     */
    public function update(Request $request): RedirectResponse
    {
        // Validar los datos de entrada
        $user = $request->user();
        
        // Crear las reglas de validación
        $rules = [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
        ];
        
        // Solo validar documento_identidad si el usuario actualmente no tiene uno o está vacío
        if (!$user->documento_identidad || trim($user->documento_identidad) === '') {
            $rules['documento_identidad'] = [
                'required',
                'string',
                'min:3',
                'max:50',
            ];
        }
        
        $rules = array_merge($rules, [
            'territorio_id' => [
                'required',
                'integer',
                Rule::exists(Territorio::class, 'id'),
            ],
            'departamento_id' => [
                'required',
                'integer',
                Rule::exists(Departamento::class, 'id')
                    ->where('territorio_id', $request->territorio_id),
            ],
            'municipio_id' => [
                'required',
                'integer',
                Rule::exists(Municipio::class, 'id')
                    ->where('departamento_id', $request->departamento_id),
            ],
            'localidad_id' => [
                'nullable',
                'integer',
                Rule::exists(Localidad::class, 'id')
                    ->where('municipio_id', $request->municipio_id),
            ],
            'telefono' => [
                'required',
                'string',
                'min:7',
                'max:15',
                'regex:/^[0-9]+$/', // Solo números
            ],
        ]);
        
        // Crear mensajes de validación
        $messages = [
            'name.required' => 'El nombre completo es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'documento_identidad.required' => 'El documento de identidad es obligatorio.',
            'documento_identidad.min' => 'El documento de identidad debe tener al menos 3 caracteres.',
            'documento_identidad.max' => 'El documento de identidad no puede tener más de 50 caracteres.',
            'territorio_id.required' => 'El territorio es obligatorio.',
            'territorio_id.exists' => 'El territorio seleccionado no es válido.',
            'departamento_id.required' => 'El departamento es obligatorio.',
            'departamento_id.exists' => 'El departamento seleccionado no es válido para este territorio.',
            'municipio_id.required' => 'El municipio es obligatorio.',
            'municipio_id.exists' => 'El municipio seleccionado no es válido para este departamento.',
            'localidad_id.exists' => 'La localidad seleccionada no es válida para este municipio.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.min' => 'El teléfono debe tener al menos 7 dígitos.',
            'telefono.max' => 'El teléfono no puede tener más de 15 dígitos.',
            'telefono.regex' => 'El teléfono solo debe contener números.',
        ];
        
        $validated = $request->validate($rules, $messages);

        try {
            // Usar transacción para garantizar la integridad de los datos
            DB::beginTransaction();

            // Preparar datos para actualizar
            $updateData = [
                'name' => $validated['name'],
                'territorio_id' => $validated['territorio_id'],
                'departamento_id' => $validated['departamento_id'],
                'municipio_id' => $validated['municipio_id'],
                'localidad_id' => $validated['localidad_id'] ?? null,
                'telefono' => $validated['telefono'],
            ];
            
            // Solo actualizar documento_identidad si estaba vacío
            if (!$user->documento_identidad || trim($user->documento_identidad) === '') {
                $updateData['documento_identidad'] = $validated['documento_identidad'];
            }
            
            // Actualizar la información del usuario
            $user->update($updateData);

            DB::commit();

            // Recargar las relaciones del usuario para que se reflejen en la sesión
            $user->load(['territorio', 'departamento', 'municipio', 'localidad']);

            return back()->with('status', 'location-updated');

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log del error para debugging
            \Log::error('Error al actualizar ubicación del usuario', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'error' => 'Ocurrió un error al actualizar tu información. Por favor, intenta nuevamente.',
            ]);
        }
    }
}