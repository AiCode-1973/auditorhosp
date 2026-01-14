<?php
session_start();

// Se já está logado, redireciona para index
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'db_config.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, nome, email, senha, nivel, ativo FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                if ($usuario['ativo'] == 1) {
                    // Login bem-sucedido
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nome'] = $usuario['nome'];
                    $_SESSION['usuario_email'] = $usuario['email'];
                    $_SESSION['usuario_nivel'] = $usuario['nivel'];
                    
                    // Atualizar último acesso
                    $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
                    $stmt->execute([$usuario['id']]);
                    
                    header('Location: index.php');
                    exit();
                } else {
                    $erro = 'Usuário desativado. Entre em contato com o administrador.';
                }
            } else {
                $erro = 'E-mail ou senha incorretos.';
            }
        } catch (PDOException $e) {
            $erro = 'Erro ao processar login. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | AuditorHosp</title>
    <!-- Google Fonts: Inter & Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            primary: '#00a6fb',
                            secondary: '#0582ca',
                            dark: '#0b1121',
                            accent: '#00f5d4'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #0b1121;
            background-image: 
                radial-gradient(at 0% 0%, rgba(0, 166, 251, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(5, 130, 202, 0.15) 0px, transparent 50%);
            height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .glass-card {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .glass-card:hover {
            border-color: rgba(0, 166, 251, 0.3);
            box-shadow: 0 0 30px rgba(0, 166, 251, 0.1);
        }

        .neon-input {
            background: rgba(30, 41, 59, 0.5) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            transition: all 0.3s ease;
        }

        .neon-input:focus {
            border-color: #00a6fb !important;
            box-shadow: 0 0 15px rgba(0, 166, 251, 0.3) !important;
            background: rgba(30, 41, 59, 0.8) !important;
        }

        .btn-premium {
            background: linear-gradient(135deg, #00a6fb 0%, #0582ca 100%);
            box-shadow: 0 4px 15px rgba(0, 166, 251, 0.4);
            transition: all 0.3s ease;
        }

        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 166, 251, 0.6);
            filter: brightness(1.1);
        }

        /* Floating elements for visual interest */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.4;
            animation: move 20s infinite alternate;
        }
        
        @keyframes move {
            from { transform: translate(0, 0); }
            to { transform: translate(100px, 100px); }
        }

        .animate-fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="font-sans antialiased text-slate-200">
    <!-- Background Elements -->
    <div class="orb w-96 h-96 bg-blue-600 top-[-10%] left-[-10%]"></div>
    <div class="orb w-[500px] h-[500px] bg-cyan-600 bottom-[-20%] right-[-10%]" style="animation-delay: -5s;"></div>

    <div class="w-full max-w-md px-6 animate-fade-in">
        <div class="glass-card rounded-3xl p-10 relative overflow-hidden">
            <!-- Subtle top accent line -->
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-brand-primary to-transparent opacity-50"></div>

            <div class="text-center mb-10">
                <div class="flex justify-center mb-6">
                    <div class="relative group">
                        <div class="absolute -inset-1 bg-gradient-to-r from-brand-primary to-cyan-400 rounded-2xl blur opacity-25 group-hover:opacity-75 transition duration-1000 group-hover:duration-200"></div>
                        <div class="relative bg-slate-800 rounded-2xl p-4 ring-1 ring-white/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <h1 class="text-4xl font-bold font-outfit text-white tracking-tight mb-2">
                    Auditor<span class="text-brand-primary">Hosp</span>
                </h1>
                <p class="text-slate-400 font-light tracking-wide text-sm">
                    SISTEMA DE AUDITORIA HOSPITALAR
                </p>
            </div>

            <?php if ($erro): ?>
                <div class="bg-red-500/10 border border-red-500/50 text-red-200 p-4 mb-8 rounded-xl flex items-center gap-3 animate-pulse">
                    <svg class="h-5 w-5 text-red-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-xs font-medium"><?php echo htmlspecialchars($erro); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6">
                <div class="group">
                    <label for="email" class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 px-1">
                        E-mail de Acesso
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                            </svg>
                        </div>
                        <input 
                            id="email" 
                            name="email" 
                            type="email" 
                            required 
                            class="neon-input block w-full pl-11 pr-4 py-4 rounded-xl text-sm focus:outline-none placeholder-slate-600"
                            placeholder="exemplo@hospital.com"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        >
                    </div>
                </div>

                <div>
                    <label for="senha" class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 px-1 flex justify-between">
                        <span>Senha Secreta</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input 
                            id="senha" 
                            name="senha" 
                            type="password" 
                            required 
                            class="neon-input block w-full pl-11 pr-4 py-4 rounded-xl text-sm focus:outline-none placeholder-slate-600"
                            placeholder="••••••••••••"
                        >
                    </div>
                </div>

                <div class="pt-2">
                    <button 
                        type="submit" 
                        class="btn-premium w-full flex justify-center py-4 px-4 text-white text-sm font-bold rounded-xl focus:outline-none transition-all uppercase tracking-widest"
                    >
                        Entrar no Sistema
                    </button>
                </div>
            </form>

            <div class="mt-8 pt-8 border-t border-white/5 text-center">
                <p class="text-[10px] text-slate-500 uppercase tracking-widest">
                    Acesso restrito & Monitorado
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 space-y-2 text-center">
            <p class="text-xs text-slate-500">
                &copy; <?php echo date('Y'); ?> AuditorHosp. Todos os direitos reservados.
            </p>
            <p class="text-xs text-slate-400">
                Desenvolvido por: <a href="https://aicode.dev.br" target="_blank" class="text-brand-primary hover:text-cyan-400 transition-colors font-medium">AiCode</a>
            </p>
        </div>
    </div>
</body>
</html>
