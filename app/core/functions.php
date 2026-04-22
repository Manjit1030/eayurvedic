<?php
// app/core/functions.php

// Escape output safely (XSS protection)
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Redirect helper
function redirect(string $path): void
{
    header("Location: " . BASE_URL . $path);
    exit;
}

// Check POST request
function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function is_valid_nepal_phone(string $phone): bool
{
    return preg_match('/^98[0-9]{8}$/', $phone) === 1;
}
