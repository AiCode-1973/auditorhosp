<?php
// Proteger todas as páginas que incluem este header
require_once __DIR__ . '/../auth.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AuditorHosp - Sistema de Auditoria Hospitalar</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'competencia': '#b8daff',
                        'metricas': '#90ee90',
                        'percentuais': '#d4edda',
                    }
                }
            }
        }
    </script>
    <style>
        .dropdown {
            position: relative;
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1000;
            min-width: 14rem;
        }
        .dropdown:hover .dropdown-menu {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">

<nav class="bg-blue-600 shadow-md mb-6 print:hidden">
  <div class="container mx-auto px-4">
    <div class="flex items-center justify-between h-16">
      <div class="flex items-center">
        <a class="text-white text-xl font-bold" href="index.php">AuditorHosp</a>
        <div class="hidden md:block ml-10">
          <div class="flex space-x-4 items-center">
            
            <!-- Dropdown Menu Análises -->
            <div class="relative dropdown">
              <button type="button" class="text-white hover:bg-blue-500 px-3 py-2 rounded-md text-sm font-medium flex items-center gap-1">
                Gestão Recurso de Glosa
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              <div class="dropdown-menu rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
                <div class="py-1">
                  <a href="index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                    <div class="flex items-center gap-2">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                      Relatórios
                    </div>
                  </a>
                  <a href="faturas.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                    <div class="flex items-center gap-2">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Lançamentos
                    </div>
                  </a>
                  <a href="dashboard_v2.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                    <div class="flex items-center gap-2">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                      </svg>
                      Dashboard V2
                    </div>
                  </a>
                  <a href="documentos.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                    <div class="flex items-center gap-2">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                      </svg>
                      Documentos
                    </div>
                  </a>
                </div>
              </div>
            </div>

            <!-- Dropdown Menu Gestão Mensal -->
            <div class="relative dropdown">
              <button type="button" class="text-white hover:bg-blue-500 px-3 py-2 rounded-md text-sm font-medium flex items-center gap-1">
                Gestão Guia Internação
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              <div class="dropdown-menu rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
                <div class="py-1">
                  <a href="internacoes.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                    <div class="flex items-center gap-2">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                      </svg>
                      Atendimento Internação
                    </div>
                  </a>
                  
                  <a href="relatorio_mensal.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                    <div class="flex items-center gap-2">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                      Relatório Mensal
                    </div>
                  </a>
                  <a href="dashboard_relatorio_mensal.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                    <div class="flex items-center gap-2">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                      </svg>
                      Dashboard Mensal
                    </div>
                  </a>
                  <a href="documentos_internacao.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                    <div class="flex items-center gap-2">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                      </svg>
                      Documentos
                    </div>
                  </a>
                </div>
              </div>
            </div>
            
            <!-- Dropdown Menu Gestão PA/Ambulatório -->
            <div class="relative dropdown">
              <button type="button" class="text-white hover:bg-blue-500 px-3 py-2 rounded-md text-sm font-medium flex items-center gap-1">
                Gestão PA/Ambulatório
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              <div class="dropdown-menu rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
                <div class="py-1">
                  <a href="pa_ambulatorio.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                    <div class="flex items-center gap-2">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                      </svg>
                      Atendimento PA/Amb.
                    </div>
                  </a>
                  <a href="relatorio_mensal_pa_ambulatorio.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                    <div class="flex items-center gap-2">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                      Relatório Mensal
                    </div>
                  </a>
                </div>
              </div>
            </div>

            <a href="convenios.php" class="text-white hover:bg-blue-500 px-3 py-2 rounded-md text-sm font-medium">Convênios</a>
            <a href="contratos.php" class="text-white hover:bg-blue-500 px-3 py-2 rounded-md text-sm font-medium">Contratos</a>
            <?php if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] == 'admin'): ?>
            <a href="usuarios.php" class="text-white hover:bg-blue-500 px-3 py-2 rounded-md text-sm font-medium">Usuários</a>
            <a href="logs_atendimento.php" class="text-white hover:bg-blue-500 px-3 py-2 rounded-md text-sm font-medium">Logs</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <!-- User Info and Logout -->
      <div class="flex items-center gap-4">
        <div class="hidden md:flex items-center gap-2 text-white text-sm">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          <span><?php echo isset($_SESSION['usuario_nome']) ? htmlspecialchars($_SESSION['usuario_nome']) : 'Usuário'; ?></span>
        </div>
        <a href="logout.php" class="text-white hover:bg-red-500 px-3 py-2 rounded-md text-sm font-medium flex items-center gap-1" title="Sair">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          <span class="hidden md:inline">Sair</span>
        </a>
      </div>
      <!-- Mobile menu button (simplified) -->
      <div class="-mr-2 flex md:hidden">
        <button type="button" class="bg-blue-600 inline-flex items-center justify-center p-2 rounded-md text-blue-200 hover:text-white hover:bg-blue-500 focus:outline-none" aria-controls="mobile-menu" aria-expanded="false">
          <span class="sr-only">Open main menu</span>
          <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</nav>

<div class="container mx-auto px-4">
