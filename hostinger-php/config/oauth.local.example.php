<?php
/**
 * Copie este arquivo para config/oauth.local.php e preencha com as
 * credenciais reais — esse arquivo (oauth.local.php) NÃO deve ser
 * versionado no git (já está no .gitignore) porque contém segredos que o
 * GitHub bloqueia em push (Push Protection). No servidor de produção,
 * crie oauth.local.php direto por FTP/gerenciador de arquivos do Hostinger,
 * nunca via commit.
 *
 * Deixe qualquer uma das linhas em branco ('') para manter o respectivo
 * provedor desligado — o botão de login correspondente some sozinho.
 */

define('GOOGLE_OAUTH_CLIENT_ID', '');
define('GOOGLE_OAUTH_CLIENT_SECRET', '');

define('FACEBOOK_OAUTH_APP_ID', '');
define('FACEBOOK_OAUTH_APP_SECRET', '');
