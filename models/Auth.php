<?php
function proteger() {
    if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id_usuario'])) {
        $_SESSION['mensagem'] = 'Faça login para acessar a área restrita.';
        header('Location: index.php?acao=login');
        exit;
    }
}
