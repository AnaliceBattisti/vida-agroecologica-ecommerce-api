<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => [
                'required',
                'regex:/^[a-zA-ZÀ-ÿ\s]+$/',  // regex para validar apenas letras do alfabeto (maiúsculas e minúsculas, com acento ou não) e espaços em branco.
                'min:3',
                'max:60'
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:60',
                'unique:users'
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:30'
            ],
            'apelido' => [
                'nullable',
                'string',
                'min:3',
                'max:30'
            ],
            'telefone' => [
                'required',
                'regex:/^\(\d{2}\)\s\d{5}\-\d{4}$/' // considerando telefone no formato "(99) 99999-9999"
            ],
            'cpf' => [
                'required',
                'regex:/^\d{3}\.\d{3}\.\d{3}\-\d{2}$/', // considerando cpf no formato "999.999.999-99"
                'unique:users'
            ]
            // 'rua' => [
            //     'required',
            //     'regex:/^[a-zA-ZÀ-ÿ\s]+$/',  // mesmo do "name"
            //     'max:60'
            // ],
            // 'cep' => [
            //     'required',
            //     'regex:/^\d{5}\-\d{3}$/' // considerando cep no formato "99999-999"
            // ],
            // 'numero' => [
            //     'required',
            //     'integer',
            //     'digits_between:1,4'
            // ],
            // 'complemento' => [
            //     'nullable',
            //     'string',
            //     'max:50'
            // ],
            // 'cidade' => [
            //     'required',
            //     'regex:/^[a-zA-ZÀ-ÿ\s]+$/',  // mesmo do "name"
            //     'max:60'
            // ],
            // 'estado' => [
            //     'required',
            //     'regex:/^[a-zA-ZÀ-ÿ\s]+$/',  // mesmo do "name"
            //     'max:30'
            // ],
            // 'pais' => [
            //     'required',
            //     'regex:/^[a-zA-ZÀ-ÿ\s]+$/',  // mesmo do "name"
            //     'max:30'
            // ],
            // 'bairro_id' => [
            //     'required',
            //     'integer',
            // ]
        ];
    }

    public function messages()
    {
        return [
            'required' => 'O campo :attribute é obrigatório.',
            'max' => 'O campo :attribute deve ter no máximo :max caracteres.',
            'min' => 'O campo :attribute deve ter no mínimo :min caracteres.',
            'digits_between' => 'O campo :attribute deve ter entre :min e :max dígitos.',
            'string' => 'O campo :attribute deve ser uma string.',
            'integer' => 'O campo :attribute deve ser numérico.',
            'unique' => 'O campo :attribute está sendo utilizado.',
            'email' => 'O email precisa ser válido.',
            'telefone.regex' => 'O campo telefone deve estar no formato (99) 99999-9999.',
            'cpf.regex' => 'O campo CPF deve estar no formato 999.999.999-99.',
            // 'cep.regex' => 'O campo CEP deve estar no formato 99999-999.',
        ];
    }
}
