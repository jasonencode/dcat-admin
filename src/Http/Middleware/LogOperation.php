<?php

namespace Dcat\Admin\Http\Middleware;

use Closure;
use Dcat\Admin\Admin;
use Dcat\Admin\Models\Operation;
use Dcat\Admin\Support\Helper;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LogOperation
{
    protected array $secretFields = [
        'password',
        'password_confirmation',
    ];

    protected array $except = [
        '/',
        'auth/login',
        'auth/logout',
        'admin.operations.*',
    ];

    protected array $defaultAllowedMethods = [
        'POST', 'PUT', 'DELETE',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->shouldLogOperation($request)) {
            $user = Admin::user();

            $log = [
                'user_id' => $user ? $user->getKey() : 0,
                'path'    => substr($request->path(), 0, 255),
                'method'  => $request->method(),
                'ip'      => $request->getClientIp(),
                'input'   => $this->formatInput($request->input()),
            ];

            try {
                Operation::create($log);
            } catch (Exception) {
            }
        }

        return $next($request);
    }

    /**
     * @param  array  $input
     *
     * @return string
     */
    protected function formatInput(array $input): string
    {
        foreach ($this->getSecretFields() as $field) {
            if ($field && ! empty($input[$field])) {
                $input[$field] = Str::limit($input[$field], 3, '******');
            }
        }

        return json_encode($input, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  Request  $request
     *
     * @return bool
     */
    protected function shouldLogOperation(Request $request): bool
    {
        return ! $this->inExceptArray($request)
            && $this->inAllowedMethods($request->method());
    }

    /**
     * Whether requests using this method are allowed to be logged.
     *
     * @param  string  $method
     *
     * @return bool
     */
    protected function inAllowedMethods(string $method): bool
    {
        $allowedMethods = collect($this->getAllowedMethods())->filter();

        if ($allowedMethods->isEmpty()) {
            return true;
        }

        return $allowedMethods->map(function ($method) {
            return strtoupper($method);
        })->contains($method);
    }

    /**
     * Determine if the request has a URI that should pass through CSRF verification.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return bool
     */
    protected function inExceptArray(Request $request): bool
    {
        if ($request->routeIs(admin_api_route_name('value'))) {
            return true;
        }

        foreach ($this->except() as $except) {
            if ($request->routeIs($except)) {
                return true;
            }

            $except = admin_base_path($except);

            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if (Helper::matchRequestPath($except)) {
                return true;
            }
        }

        return false;
    }

    protected function except(): array
    {
        return $this->except;
    }

    protected function getSecretFields(): array
    {
        return $this->secretFields;
    }

    protected function getAllowedMethods(): array
    {
        return $this->defaultAllowedMethods;
    }
}