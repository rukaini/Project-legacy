<?php

namespace App\Services;

class SystemRegistry
{
    public function role(string $role): ?array
    {
        $config = config("sso.roles.{$role}");

        return is_array($config) ? $config : null;
    }

    public function system(string $key): ?array
    {
        $config = config("sso.systems.{$key}");

        return is_array($config) ? $config : null;
    }

    public function allows(string $role, string $system): bool
    {
        $roleConfig = $this->role($role);
        $systemConfig = $this->system($system);

        return $roleConfig !== null && $systemConfig !== null
            && $this->isAvailable($systemConfig)
            && in_array($system, $roleConfig['systems'] ?? [], true)
            && in_array($role, $systemConfig['roles'] ?? [], true);
    }

    public function systemsFor(string $role): array
    {
        $systems = [];
        foreach ($this->role($role)['systems'] ?? [] as $key) {
            if ($this->allows($role, $key) && ($system = $this->system($key))) {
                $systems[$key] = $system;
            }
        }

        return $systems;
    }

    public function callbackUrl(string $system): string
    {
        $config = $this->system($system) ?? [];

        return rtrim((string) ($config['url'] ?? ''), '/').'/'.ltrim((string) ($config['callback_path'] ?? ''), '/');
    }

    private function isAvailable(array $system): bool
    {
        return ($system['enabled'] ?? false) === true
            && filter_var($system['url'] ?? null, FILTER_VALIDATE_URL) !== false
            && (string) ($system['secret'] ?? '') !== '';
    }
}
