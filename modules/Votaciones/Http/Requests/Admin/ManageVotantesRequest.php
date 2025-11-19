<?php

namespace Modules\Votaciones\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ManageVotantesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('votaciones.manage_voters');
    }

    public function rules(): array
    {
        if ($this->isMethod('POST')) {
            return [
                'votante_ids' => 'required|array',
                'votante_ids.*' => 'exists:users,id',
            ];
        }

        if ($this->isMethod('DELETE')) {
            return [
                'votante_id' => 'required|exists:users,id',
            ];
        }

        return [];
    }
}
