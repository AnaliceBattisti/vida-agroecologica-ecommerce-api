<?php

use App\Http\Controllers\Api\BairroController;
use App\Http\Controllers\Api\BancaController as ApiBancaController;
use App\Http\Controllers\Api\ConsumidorController;
use App\Http\Controllers\Api\EnderecoController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\ProdutoController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProdutorController;
use App\Http\Controllers\BancaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::controller(EnderecoController::class)->group(function () {
        Route::get('/enderecos', 'show');
        Route::put('/enderecos', 'update');
    });
    Route::controller(BairroController::class)->group(function () {
        Route::get('bairros', 'index');
    });
    //produtor
    Route::middleware('check_produtor')->group(function () {
        Route::apiResource('/produtores', ProdutorController::class, ['parameters' => ['produtores' => 'produtor']])->except('store');

        Route::controller(ApiBancaController::class)->group(function () {
            Route::post('bancas', 'store')->middleware('check_bancas');
            Route::get('bancas', 'index');
            Route::get('bancas/{banca}', 'show');
            Route::put('bancas/{banca}', 'update');
            Route::delete('bancas/{banca}', 'destroy')->middleware('check_valid_banca');
        });

        Route::apiResource('banca/produtos', ProdutoController::class);
    });
    //consumidor
    Route::middleware('check_consumidor')->group(function () {
        Route::apiResource('/consumidores', ConsumidorController::class, ['parameters' => ['consumidores' => 'consumidor']])->except('store');
    });
    //fora dos middlewares
    Route::get('/categorias', function (Request $request) {

        return response()->json(['categorias' => \App\Models\Categoria::all()]);
    });
    Route::controller(ProdutoController::class)->group(function () {
        Route::post('/busca', 'buscar');
        Route::get('/categorias/{categoria}/produtos', 'buscarCategoria');
    });
    Route::get('/produtos', function() {
        $produtos = App\Models\ProdutoTabelado::all();
        return response()->json(['produtos' => $produtos]);
    });
});

Route::post('/produtores', [ProdutorController::class, 'store']);

Route::post('/consumidores', [ConsumidorController::class, 'store']);

Route::post('/login', [LoginController::class, 'login']);
Route::post('/token', [LoginController::class, 'token']);
