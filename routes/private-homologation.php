<?php

use App\Homologation\Controller;
use Illuminate\Support\Facades\Route;

Route::get('/',[Controller::class,'home']);
Route::get('/cadastro',[Controller::class,'signup']);
Route::post('/cadastro',[Controller::class,'issue']);
Route::get('/cadastro/codigo',[Controller::class,'otp']);
Route::post('/cadastro/reenviar',[Controller::class,'resend']);
Route::post('/cadastro/verificar',[Controller::class,'verify']);
Route::get('/cadastro/mfa',[Controller::class,'setup']);
Route::post('/cadastro/concluir',[Controller::class,'complete']);
Route::get('/login',[Controller::class,'login']);
Route::post('/login',[Controller::class,'password']);
Route::get('/mfa',[Controller::class,'mfa']);
Route::post('/mfa',[Controller::class,'factor']);
Route::post('/logout',[Controller::class,'logout']);
Route::get('/recuperar',[Controller::class,'recovery']);
Route::post('/recuperar',[Controller::class,'recover']);
Route::get('/painel',[Controller::class,'dashboard']);
Route::post('/leads',[Controller::class,'leads']);
Route::post('/leads/{id}',[Controller::class,'lead']);
Route::post('/tarefas',[Controller::class,'tasks']);
Route::post('/tarefas/{id}',[Controller::class,'task']);
Route::post('/preferencias',[Controller::class,'preferences']);
Route::post('/pedidos',[Controller::class,'order']);
Route::post('/pedidos/{id}/simular',[Controller::class,'payment']);
Route::get('/exportar',[Controller::class,'export']);
Route::post('/importar',[Controller::class,'import']);
Route::get('/admin',[Controller::class,'admin']);
Route::post('/admin/contas/{id}',[Controller::class,'status']);
Route::post('/funis',[Controller::class,'funnel']);
Route::post('/equipe/convidar',[Controller::class,'invite']);
Route::get('/convite',[Controller::class,'invitationForm']);
Route::post('/convite',[Controller::class,'invitationIssue']);
