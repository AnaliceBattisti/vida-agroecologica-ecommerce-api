<?php

namespace App\Http\Controllers\Api;

use App\Events\PedidoConfirmado;
use App\Models\ItemVenda;
use App\Models\Produtor;
use Brick\Math\BigDecimal;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVendaRequest;
use App\Models\FormaPagamento;
use App\Models\Produto;
use App\Models\Venda;
use App\Notifications\EnviarEmailCompra;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VendaController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $transacoes = $user->papel_type == 'Produtor'
            ? $user->papel->vendas->load('itens') //carrega os itens de cada venda
            : $user->papel->compras->load('itens');
        return response()->json(['transações' => $transacoes], 200);
    }

    public function store(StoreVendaRequest $request)
    {
        $consumidor = Auth::user()->papel;

        DB::beginTransaction();
        $venda = new Venda();
        $venda->status = 'pedido realizado';
        $venda->data_pedido = now();
        $venda->consumidor()->associate($consumidor);
        $venda->produtor()->associate(Produtor::find($request->produtor));
        $formaPagamento = FormaPagamento::find($request->forma_pagamento);
        $venda->formaPagamento()->associate($formaPagamento);
        $venda->save();
        $subtotal = BigDecimal::of('0.00');
        $taxaEntrega = BigDecimal::of(Auth::user()->endereco->bairro->taxa);
        $itens = [];

        foreach ($request->produtos as $produto) {
            $prod = Produto::findOrFail($produto[0]); // índice 0: id do produto; índice 1: quantidade do produto.

            if ($produto[1] > $prod->estoque || !$prod->disponivel) {
                DB::rollBack();
                return response()->json(['error' => 'A quantidade solicitada ultrapassa o estoque, ou o produto não está a venda.', 'produto' => $prod], 400);
            } elseif ($request->produtor != $prod->banca->produtor->id) {
                DB::rollBack();
                return response()->json(['error' => 'O produto não pertence à banca do produtor especificado', 'produto' => $prod], 400);
            }

            $item = new ItemVenda();
            $item->tipo_unidade = $prod->tipo_unidade;
            $item->quantidade = $produto[1];
            $item->preco = BigDecimal::of($prod->preco);
            $item->venda()->associate($venda);
            $item->produto()->associate($prod);
            $item->save();
            array_push($itens, $item->makeHidden('venda'));
            $subtotal = $subtotal->plus(BigDecimal::of($prod->preco)->multipliedBy($produto[1])); // preço x quantidade
            $prod->estoque -= $produto[1];
            $prod->save();
        }

        $venda->subtotal = $subtotal;
        $venda->taxa_entrega = $taxaEntrega;
        $venda->total = $subtotal->plus($taxaEntrega);
        $venda->save();
        event(new PedidoConfirmado($venda));
        DB::commit();
        $consumidor->user->notify(new EnviarEmailCompra($venda));
        return response()->json(['venda' => $venda->makeHidden('consumidor'), 'consumidor' => $consumidor->user->makeHidden('endereco'), 'endereço' => $consumidor->user->endereco, 'itens' => $itens], 200);
    }

    public function show($id)
    {
        $venda = Venda::findOrFail($id);
        $comprovante = $venda->comprovante_pagamento != null;
        return response()->json(['venda' => $venda->makeHidden('comprovante_pagamento'), 'comprovante' => $comprovante]);
    }

    public function confirmarVenda(Request $request, $id)
    {
        $request->validate(['confirmacao' => 'required']);
        $venda = Venda::findOrFail($id);
        if ($venda->status != 'pedido realizado') {
            return response()->json(['erro' => 'Esta venda já foi confirmada ou recusada'], 400);
        }

        DB::beginTransaction();
        if ($request->confirmacao) {
            $venda->status = 'pagamento pendente';
            $venda->data_confirmacao = now();
            $venda->save();
            event(new PedidoConfirmado($venda));
        } else {
            $this->cancelarCompra('produtor', $venda->id);
        }
        DB::commit();
    }

    public static function cancelarCompra($id)
    {
        $user = Auth::user();
        $venda = Venda::findOrFail($id);
        if ($venda->status != 'pedido realizado' && $venda->status != 'pagamento pendente') {
            return response()->json(['erro' => 'Esta venda não pode mais ser cancelada.'], 400);
        }
        DB::beginTransaction();
        foreach ($venda->itens as $item) {
            $produto = $item->produto;
            $produto->estoque += $item->quantidade;
            $produto->save();
        }
        $status = '';
        switch ($user->papel) {
            case 'consumidor':
                $status = 'pedido cancelado';
            case 'produtor':
                $status = 'pedido recusado';
            default:
                $status = 'pagamento expirado';
        }
        $venda->status = $status;
        $venda->data_cancelamento = now();
        $venda->save();
        DB::commit();
        return response()->json(['sucess' => 'Pedido cancelado'], 200);
    }

    public function anexarComprovante(Request $request, $id)
    {
        $venda = Venda::findOrFail($id);
        if (!Gate::allows('anexar_comprovante', $venda)) {
            abort(403);
        }

        $request->validate(['comprovante' => 'required|file|mimes:jpeg,png,pdf|max:2048']);
        $conteudo = base64_encode(file_get_contents($request->file('comprovante')->path()));
        $venda->comprovante_pagamento = $conteudo;
        $venda->save();

        return response(base64_decode($venda->comprovante_pagamento))->header('Content-Type', $request->file('comprovante')->getMimeType());
    }

    public function verComprovante($id)
    {
        $venda = Venda::findOrFail($id);
        $file = base64_decode($venda->comprovante_pagamento);

        if (!$file) {
            return response()->json(['error' => 'A venda não possui comprovante de pagamento'], 404);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $file);
        finfo_close($finfo);
        return response($file)->header('Content-Type', $mimeType);
    }
}
