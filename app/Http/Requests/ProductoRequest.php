<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $producto = $this->route('producto');

        return [
            'sku' => ['required', 'string', 'max:30', Rule::unique('productos', 'sku')->ignore($producto)],
            'nombre' => ['required', 'string', 'max:120'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
        ];
    }
}
