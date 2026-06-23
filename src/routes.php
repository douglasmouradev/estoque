<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\BuscaController;
use App\Controllers\CategoriaController;
use App\Controllers\ClienteController;
use App\Controllers\ConfigController;
use App\Controllers\DashboardController;
use App\Controllers\FornecedorController;
use App\Controllers\HealthController;
use App\Controllers\OrcamentoController;
use App\Controllers\OrdemServicoController;
use App\Controllers\PecaController;
use App\Controllers\PortalOrcamentoController;
use App\Controllers\RelatorioController;
use App\Controllers\SenhaController;
use App\Controllers\ServicoController;
use App\Controllers\UserController;
use App\Controllers\VeiculoController;
use App\Core\Router;

$router = new Router();

$health = new HealthController();
$router->get('/health', [$health, 'check'], false);

$portal = new PortalOrcamentoController();
$router->get('/portal/orcamento/{token}', [$portal, 'show'], false);
$router->post('/portal/orcamento/{token}/aprovar', [$portal, 'aprovar'], false);
$router->post('/portal/orcamento/{token}/reprovar', [$portal, 'reprovar'], false);

$senha = new SenhaController();
$router->get('/trocar-senha', [$senha, 'form']);
$router->post('/trocar-senha', [$senha, 'atualizar']);

$busca = new BuscaController();
$router->get('/busca', [$busca, 'global']);

$rel = new RelatorioController();
$router->get('/relatorios', [$rel, 'index']);

$serv = new ServicoController();
$router->get('/servicos', [$serv, 'index']);
$router->get('/servicos/todos', [$serv, 'todos']);
$router->post('/servicos', [$serv, 'store']);
$router->put('/servicos/{id}', [$serv, 'update']);
$router->delete('/servicos/{id}', [$serv, 'destroy']);

$auth = new AuthController();
$router->get('/login', [$auth, 'loginForm'], false);
$router->post('/login', [$auth, 'login'], false);
$router->post('/logout', [$auth, 'logout']);

$dash = new DashboardController();
$router->get('/', [$dash, 'index']);

$clientes = new ClienteController();
$router->get('/clientes', [$clientes, 'index']);
$router->get('/clientes/buscar', [$clientes, 'buscar']);
$router->get('/clientes/{id}', [$clientes, 'show']);
$router->post('/clientes', [$clientes, 'store']);
$router->put('/clientes/{id}', [$clientes, 'update']);
$router->delete('/clientes/{id}', [$clientes, 'destroy']);

$veiculos = new VeiculoController();
$router->get('/veiculos/placa', [$veiculos, 'buscarPlaca']);
$router->get('/veiculos/cliente/{clienteId}', [$veiculos, 'listarPorCliente']);
$router->get('/veiculos/{id}/historico', [$veiculos, 'historico']);
$router->post('/veiculos', [$veiculos, 'store']);
$router->put('/veiculos/{id}', [$veiculos, 'update']);
$router->delete('/veiculos/{id}', [$veiculos, 'destroy']);

$pecas = new PecaController();
$router->get('/estoque', [$pecas, 'index']);
$router->get('/estoque/paradas', [$pecas, 'paradas']);
$router->get('/estoque/autocomplete', [$pecas, 'autocomplete']);
$router->post('/estoque/importar', [$pecas, 'importarCsv']);
$router->get('/estoque/{id}', [$pecas, 'show']);
$router->post('/estoque', [$pecas, 'store']);
$router->put('/estoque/{id}', [$pecas, 'update']);
$router->delete('/estoque/{id}', [$pecas, 'destroy']);
$router->post('/estoque/{id}/movimentar', [$pecas, 'movimentar']);
$router->post('/estoque/{id}/fornecedor', [$pecas, 'salvarFornecedor']);

$forn = new FornecedorController();
$router->get('/fornecedores', [$forn, 'index']);
$router->get('/fornecedores/todos', [$forn, 'listarTodos']);
$router->post('/fornecedores', [$forn, 'store']);
$router->put('/fornecedores/{id}', [$forn, 'update']);
$router->delete('/fornecedores/{id}', [$forn, 'destroy']);

$cat = new CategoriaController();
$router->get('/categorias', [$cat, 'index']);
$router->post('/categorias', [$cat, 'store']);
$router->put('/categorias/{id}', [$cat, 'update']);
$router->delete('/categorias/{id}', [$cat, 'destroy']);

$orc = new OrcamentoController();
$router->get('/orcamentos', [$orc, 'index']);
$router->get('/orcamentos/{id}', [$orc, 'show']);
$router->get('/orcamentos/{id}/pdf', [$orc, 'pdf']);
$router->post('/orcamentos', [$orc, 'store']);
$router->put('/orcamentos/{id}', [$orc, 'update']);
$router->post('/orcamentos/{id}/enviar', [$orc, 'enviar']);
$router->post('/orcamentos/{id}/aprovar', [$orc, 'aprovar']);
$router->post('/orcamentos/{id}/reprovar', [$orc, 'reprovar']);
$router->post('/orcamentos/{id}/converter-os', [$orc, 'converterOs']);

$os = new OrdemServicoController();
$router->get('/os', [$os, 'index']);
$router->post('/os', [$os, 'store']);
$router->get('/os/{id}', [$os, 'show']);
$router->get('/os/{id}/pdf', [$os, 'pdf']);
$router->post('/os/{id}/status', [$os, 'atualizarStatus']);
$router->post('/os/{id}/finalizar', [$os, 'finalizar']);
$router->post('/os/{id}/itens', [$os, 'adicionarItem']);
$router->delete('/os/{id}/itens/{itemId}', [$os, 'removerItem']);
$router->post('/os/{id}/itens/{itemId}/toggle', [$os, 'toggleItem']);
$router->post('/os/{id}/checklist', [$os, 'adicionarChecklist']);
$router->post('/os/checklist/{itemId}/toggle', [$os, 'toggleChecklist']);
$router->post('/os/{id}/horas', [$os, 'registrarHoras']);
$router->post('/os/{id}/pagamento', [$os, 'registrarPagamento']);

$config = new ConfigController();
$router->get('/config', [$config, 'index'], true, true);
$router->post('/config', [$config, 'salvar'], true, true);

$users = new UserController();
$router->get('/usuarios', [$users, 'index'], true, true);
$router->post('/usuarios', [$users, 'store'], true, true);
$router->put('/usuarios/{id}', [$users, 'update'], true, true);
$router->delete('/usuarios/{id}', [$users, 'destroy'], true, true);

return $router;
