<?php

namespace App\Http\Controllers;

use App\Models\Reuniao;
use App\Models\Associacao;
use App\Models\User;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;


use Illuminate\Support\Facades\Auth;

class ReuniaoController extends Controller
{
    public function index()
    {
        $reunioes = Reuniao::all();

        return response()->json(['reunioes'=> $reunioes]);
    }
    
    public function store(Request $request)
    {
        if($request->user()->hasAnyRoles(['presidente', 'secretario', 'administrador']))
        {
            $reuniao = Reuniao::create($request->all());
            $associacao = Associacao::findOrFail($request->associacao_id);
            $reuniao->associacao()->associate($associacao);
            
            return response()->json(['reuniao' => $reuniao]);
        }else{
            $reuniao = Reuniao::create($request->except('status'))->refresh();
            $associacao = Associacao::findOrFail($request->associacao_id);
            $reuniao->associacao()->associate($associacao);
            return response()->json(['reuniao' => $reuniao]);
        }
       
    }

    public function update(Request $request, $id)
    {
        if($request->user()->hasAnyRoles(['presidente', 'secretario', 'administrador']))
        {
            $reuniao = Reuniao::findOrFail($id);

            $reuniao->update($request->only('status, tipo, data'));

            return response()->json(['reuniao' => $reuniao->refresh()]);
        }else{
            return response()->json('usuário não autorizado');
        }
        
    }

    public function destroy($id)
    {
        $reuniao = Reuniao::findOrFail($id);
        
        $reuniao->delete();

        return response()->noContent();
    }

    public function gerarAta(Request $request, $id)
    {
        $data = Reuniao::findOrFail($id)->data->format('d-m-Y');
        $reuniao = Reuniao::findOrFail($id);
        $titulo = $reuniao->titulo;
        $detalhamento = $reuniao->detalhamento;
        $nomes = User::all();

        $membros = [];
        foreach($nomes as $nome){
            $membros[] = $nome->name;
        }

        $pdf = Pdf::loadView('gerarpdf', compact('data', 'membros', 'titulo', 'detalhamento'));
        
        $ataReuniao = 'reuniao.pdf';
        return $pdf->setPaper('a4')->stream($ataReuniao);

    }
}

