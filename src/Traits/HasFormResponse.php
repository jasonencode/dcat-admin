<?php

namespace Dcat\Admin\Traits;

use Dcat\Admin\Admin;
use Dcat\Admin\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Validator;

trait HasFormResponse
{
    protected string $currentUrl = '';

    /**
     * @return JsonResponse
     */
    public function response(): JsonResponse
    {
        return Admin::json();
    }

    /**
     * 返回字段验证错误信息.
     *
     * @param  \Illuminate\Validation\Validator|array|MessageBag  $validationMessages
     * @return \Illuminate\Http\JsonResponse
     */
    public function validationErrorsResponse(Validator|array|MessageBag $validationMessages
    ): \Illuminate\Http\JsonResponse {
        return $this
            ->response()
            ->withValidation($validationMessages)
            ->send();
    }

    /**
     * 设置当前URL.
     *
     * @param  string  $url
     * @return $this
     */
    public function setCurrentUrl(string $url): static
    {
        $this->currentUrl = admin_url($url);

        return $this;
    }

    /**
     * 获取当前URL.
     *
     * @param  string|null  $default
     * @param  Request|null  $request
     * @return string
     */
    protected function getCurrentUrl(string $default = null, Request $request = null): string
    {
        if ($this->currentUrl) {
            return admin_url($this->currentUrl);
        }

        /* @var Request $request */
        $request = $request ?: (empty($this->request) ? request() : $this->request);

        if ($current = $request->get(static::CURRENT_URL_NAME)) {
            return admin_url($current);
        }

        if ($default !== null) {
            return $default;
        }

        $query = $request->query();

        if (method_exists($this, 'sanitize')) {
            $query = $this->sanitize($query);
        }

        return url($request->path().'?'.http_build_query($query));
    }

    /**
     * 响应数据.
     *
     * @param $response
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    protected function sendResponse($response): mixed
    {
        if ($response instanceof JsonResponse) {
            return $response->send();
        }

        return $response;
    }
}
