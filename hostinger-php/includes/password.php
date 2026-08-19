<?php
/**
 * Hash de senha: Argon2id quando o PHP tem suporte (algoritmo recomendado
 * atualmente, resistente a ataques por hardware dedicado/GPU), com fallback
 * automático pra bcrypt em custo alto se a extensão não estiver disponível
 * no servidor (alguns hosts compilam o PHP sem libargon2). Nunca MD5/SHA
 * puro — essas funções são rápidas demais e continuam permitindo forçar
 * senha por força bruta mesmo com um hash "correto".
 */

function password_algo(): string|int
{
    return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
}

function password_algo_options(): array
{
    return defined('PASSWORD_ARGON2ID')
        ? ['memory_cost' => 1 << 16, 'time_cost' => 4, 'threads' => 2] // ~64MB, defaults do PHP endurecidos
        : ['cost' => 12];
}

function hash_password(string $plain): string
{
    return password_hash($plain, password_algo(), password_algo_options());
}

/**
 * Confere a senha e devolve um novo hash pra salvar SE o hash guardado
 * estiver desatualizado (ex.: conta antiga em bcrypt custo 10, ou o
 * servidor ganhou suporte a Argon2id depois de criada) — assim as contas
 * migram pro algoritmo/custo atual de forma transparente, sem precisar de
 * migração em massa nem pedir pro usuário trocar de senha.
 */
function verify_password(string $plain, string $hash): array
{
    $valid = password_verify($plain, $hash);
    $rehash = $valid && password_needs_rehash($hash, password_algo(), password_algo_options())
        ? hash_password($plain)
        : null;
    return ['valid' => $valid, 'rehash' => $rehash];
}
