<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizacaoRequest;
use App\Http\Requests\UpdateOrganizacaoRequest;
use App\Models\Associacao;
use App\Models\Contato;
use App\Models\Endereco;
use App\Models\Estado;
use App\Models\OrganizacaoControleSocial;
use App\Models\User;
use Illuminate\Support\Facades\DB;


class OrganizacaoControleSocialController extends Controller
{
    public function index()
    {
        $listaOcs = OrganizacaoControleSocial::with('associacao', 'endereco', 'contato', 'endereco.bairro')->get();

        return response()->json(['ocs'=>$listaOcs]);
    }

    public function show($id)
    {
        $ocs = OrganizacaoControleSocial::with('associacao', 'endereco', 'contato', 'endereco.bairro')->findOrFail($id);
        return response()->json(['ocs' => $ocs]);
    }

    public function store(StoreOrganizacaoRequest $request)
{
    DB::beginTransaction();
    $organizacaoData = $request->only('nome', 'cnpj', 'associacao_id', 'user_id');
    $organizacao = OrganizacaoControleSocial::create($organizacaoData);


    if ($request->has('email') && $request->has('telefone')) {
        $contatoData = $request->only('email', 'telefone');
        $organizacao->contato()->create($contatoData);
    }

    $organizacao->endereco()->create($request->only('rua', 'cep', 'numero', 'bairro_id', 'complemento'));

    foreach ($request->agricultores_id as $key) {
        $user = User::find($key);
        $user->organizacao()->associate($organizacao)->save();
    }

    DB::commit();
    return response()->json(['organizacao' => $organizacao->load(['agricultores', 'contato', 'endereco', 'associacao'])]);
}

    public function update(UpdateOrganizacaoRequest $request, $id)
    {
        $organizacao = OrganizacaoControleSocial::where('id', $id)->first();

        if (!$organizacao) {
            return response()->json(['message' => 'OCS não encontrada.'], 404);
        }
        $organizacao = OrganizacaoControleSocial::findOrFail($id);
        $organizacao->update($request->only('nome','cnpj'));
        $organizacao->endereco()->update($request->only('rua','numero','cep','bairro_id'));
        $organizacao->contato->update($request->only('email', 'telefone'));
        foreach ($request->agricultores_id as $key)
        {
            $user = User::find($key);
            $user->organizacao()->associate($organizacao)->save();
        }

        return response()->json(['organizacao' => $organizacao->load(['agricultores', 'contato', 'endereco', 'associacao'])]);

    }
    public function destroy($id)
    {
        $organizacao = OrganizacaoControleSocial::findOrFail($id);

        $organizacao->delete();

        return response()->json($organizacao);
    }
}


// $associacao = Associacao::where('id', $id)->first();

// if (!$associacao) {
//     return response()->json(['message' => 'Associação não encontrada.'], 404);
// }

// $associacao->update($request->only('nome', 'data_fundacao'));
// $associacao->contato()->update($request->except('_token', 'nome', 'data_fundacao','presidentes_id', 'secretarios_id', 'cep', 'rua', 'numero', 'bairro_id'));
// $associacao->endereco()->update($request->except('_token', 'nome', 'data_fundacao','presidentes_id', 'secretarios_id', 'email','telefone'));

// $associacao->presidentes()->sync($request->input('presidentes_id'));
// $associacao->secretarios()->sync($request->input('secretarios_id'));

// return response()->json(['associacao' => $associacao->load(['presidentes', 'contato', 'secretarios', 'endereco'])]);
