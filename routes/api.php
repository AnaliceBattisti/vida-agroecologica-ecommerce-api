<?php

use App\Http\Controllers\Api\EstadoController;
use App\Http\Controllers\Api\BairroController;
use App\Http\Controllers\Api\BancaController;
use App\Http\Controllers\Api\CidadeController;
use App\Http\Controllers\Api\ProdutoController;
use App\Http\Controllers\Api\UserConsumidorController;
use App\Http\Controllers\Api\VendaController;
use App\Http\Controllers\Api\FeiraController;
use App\Http\Controllers\Api\ResetPasswordController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Auth\Api\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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

// Consumidor
Route::middleware('auth:sanctum')->controller(UserConsumidorController::class)->prefix('/users')->group(function () {
    Route::get('/enderecos', 'indexEndereco');
    Route::post('/enderecos', 'storeEndereco');
    Route::get('/enderecos/{id}', 'showEndereco');
    Route::patch('/enderecos/{id}', 'updateEndereco');
    Route::delete('/enderecos/{id}', 'destroyEndereco');
});

// Usuário
Route::controller(UserController::class)->group(function () {
    Route::post('/users', 'store');
    Route::put('/users/{id}/updateroles', 'updateUserRoles')->middleware('auth:sanctum, role:administrador');
});

Route::apiResource('/users', UserController::class)->except('store')->middleware('auth:sanctum');

// banca
Route::apiResource('/bancas', BancaController::class)->middleware('auth:sanctum');
Route::delete('/bancas/{id}/imagens', [BancaController::class, 'deleteImagem'])->middleware('auth:sanctum');
Route::get('/imagens/bancas/{banca}', [BancaController::class, 'getImagem']);

// venda
Route::middleware('auth:sanctum')->controller(VendaController::class)->prefix('/vendas')->group(function () {
    Route::post('/{id}/confirmar', 'confirmarVenda')->middleware('role:agricultor');
    Route::post('/{id}/enviar', 'marcarEnviado')->middleware('role:agricultor');

    Route::post('/{id}/comprovante', 'anexarComprovante')->middleware('role:consumidor');
    Route::post('/{id}/entregar', 'marcarEntregue')->middleware('role:consumidor');
    Route::post('/', 'store')->middleware('role:consumidor');

    Route::get('/', 'index')->middleware('role:administrador');

    Route::post('/{id}/cancelar', 'cancelarCompra');
    Route::get('/{id}/comprovante', 'verComprovante');
    Route::get('/{id}', 'show');
});

// Feiras
Route::middleware('auth:sanctum')->controller(FeiraController::class)->prefix('feiras')->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store')->middleware('role:administrador');
});

// Bairros
Route::middleware('auth:sanctum')->controller(BairroController::class)->prefix('bairros')->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store')->middleware('role:administrador');
});

// Cidades
Route::middleware('auth:sanctum')->controller(CidadeController::class)->prefix('cidades')->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store')->middleware('role:administrador');
});

// --------------------------------------------------------------------------------------------------------------------------------------------------------

Route::middleware(['auth:sanctum'])->group(function () {

    Route::controller(BairroController::class)->group(function () {
        Route::get('bairros', 'index');
    });


    Route::apiResource('banca/produtos', ProdutoController::class);


    Route::get('/categorias', function () {
        return response()->json(['categorias' => App\Models\ProdutoTabelado::distinct()->pluck('categoria')]);
    });
    Route::controller(ProdutoController::class)->group(function () {
        Route::post('/busca', 'buscar');
        Route::get('/categorias/{categoria}/produtos', 'buscarCategoria');
    });
    Route::get('/produtos', function () {
        $produtos = App\Models\ProdutoTabelado::all();
        return response()->json(['produtos' => $produtos], 200);
    });
});


Route::get('/login', fn () => response()->json(['error' => 'Login necessário'], 401))->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/token', [LoginController::class, 'token']);

Route::get('/email/verify', function () {
    return response()->json(['error' => 'O usuário não está verificado!', 'email' => Auth::user()->email], 403);
})->middleware('auth:sanctum')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [LoginController::class, 'verificarEmail'])->middleware('signed')->name('verification.verify');
Route::post('/email/verification-notification', [LoginController::class, 'reenviarEmail'])->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');

// Rota para solicitar o email de redefinição de senha


Route::prefix('estados')->group(function () {
    Route::get('/', [EstadoController::class, 'index']);
});

Route::post('/forgot-password', [ResetPasswordController::class, 'sendResetEmail'])->name('password.email');

Route::get('/imagens/produtos/{id}', [ProdutoController::class, 'getImagem']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
});

// Parte do gesão web

Route::middleware(['auth:sanctum', 'type.admin'])->group(function () {
    // Usuario
    Route::post('cadastro', [UserController::class, 'store']);
    Route::post('atualizar/usuario', [UserController::class, 'update']);
    Route::get('users', [UserController::class, 'index']);

    // Associacao
    Route::get('associacoes', [\App\Http\Controllers\Auth\Api\AssociacaoController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'type.admin.presidente'])->group(function () {
    //Associacao
    Route::post('cadastrar/associacao', [\App\Http\Controllers\Auth\Api\AssociacaoController::class, 'store']);
    Route::post('atualizar/associacao', [\App\Http\Controllers\Auth\Api\AssociacaoController::class, 'update']);

    // OCS
    Route::post('/organizacaoControleSocial/store', [App\Http\Controllers\Api\OrganizacaoControleSocialController::class, 'store']);
    Route::post('/organizacaoControleSocial/update', [App\Http\Controllers\Api\OrganizacaoControleSocialController::class, 'update']);
    Route::get('/associacao/{associacao_id}/organizacaoControleSocial', [App\Http\Controllers\Api\OrganizacaoControleSocialController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'type.presidente'])->group(function () {
    //minhas associações
});

Route::middleware(['auth:sanctum', 'type.agricultor'])->group(function () {

    // Propriedade
    Route::post('/propriedade/store', [App\Http\Controllers\Api\PropriedadeController::class, 'store']);
    Route::post('/propriedade/update', [App\Http\Controllers\Api\PropriedadeController::class, 'update']);
    Route::get('/usuario/{user_id}/propriedades', [App\Http\Controllers\Api\PropriedadeController::class, 'index']);
});

Route::post('/verifica', [UserController::class, 'verificaUsuario']);
