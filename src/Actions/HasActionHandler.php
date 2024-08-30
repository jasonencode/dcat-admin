<?php

namespace Dcat\Admin\Actions;

use Dcat\Admin\Admin;
use Dcat\Admin\Traits\HasAuthorization;

trait HasActionHandler
{
    use HasAuthorization {
        HasAuthorization::failedAuthorization as parentFailedAuthorization;
    }

    /**
     * @var Response|null
     */
    protected ?Response $response = null;

    private string|array|null $confirmString = null;

    private ?string $paramString = null;

    /**
     * @return Response
     */
    public function response(): Response
    {
        if (is_null($this->response)) {
            $this->response = new Response();
        }

        return $this->response;
    }

    /**
     * @return string
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * @return array
     */
    protected function parameters(): array
    {
        return [];
    }

    /**
     * Confirm message of action.
     */
    public function confirm()
    {
    }

    /**
     * @return string|array
     */
    public function makeCalledClass(): string|array
    {
        return str_replace('\\', '_', get_called_class());
    }

    /**
     * @return string
     */
    public function handlerRoute(): string
    {
        return route(admin_api_route_name('action'));
    }

    /**
     * @return string
     */
    protected function normalizeConfirmData(): string
    {
        if ($this->confirmString !== null) {
            return $this->confirmString;
        }

        $confirm = $this->confirm();

        return $this->confirmString = ($confirm ? admin_javascript_json((array) $confirm) : 'false');
    }

    /**
     * @return string
     */
    protected function normalizeParameters(): string
    {
        return $this->paramString ?: ($this->paramString = json_encode($this->parameters()));
    }

    /**
     * @return void
     */
    protected function addHandlerScript(): void
    {
        $script = <<<JS
            Dcat.Action({
                selector: '{$this->selector()}',
                event: '$this->event',
                method: '{$this->method()}',
                key: '{$this->getKey()}',
                url: '{$this->handlerRoute()}',
                data: {$this->normalizeParameters()},
                confirm: {$this->normalizeConfirmData()},
                calledClass: '{$this->makeCalledClass()}',
                before: {$this->actionScript()},
                html: {$this->handleHtmlResponse()},
                success: {$this->resolverScript()},
                error: {$this->rejectScript()},
            });
            JS;

        Admin::script($script);
        Admin::js('@admin/dcat/extra/action.js');
    }

    /**
     * 设置动作发起请求前的回调函数，返回false可以中断请求.
     *
     * @return string
     */
    protected function actionScript(): string
    {
        // 发起请求之前回调，返回false可以中断请求
        return <<<JS
            function(data, target, action) { }
            JS;
    }

    /**
     * 设置请求成功回调，返回false可以中断默认的成功处理逻辑.
     *
     * @return string
     */
    protected function resolverScript(): string
    {
        // 请求成功回调，返回false可以中断默认的成功处理逻辑
        return <<<JS
            function(target, results) {}
            JS;
    }

    /**
     * 处理接口返回的HTML代码.
     *
     * @return string
     */
    protected function handleHtmlResponse(): string
    {
        return <<<JS
            function(target, html, data) {
                target.html(html);
            }
            JS;
    }

    /**
     * 设置请求出错回调，返回false可以中断默认的错误处理逻辑.
     *
     * @return string
     */
    protected function rejectScript(): string
    {
        return <<<JS
function(target, results) {}
JS;
    }

    /**
     * @return Response
     */
    public function failedAuthorization(): Response
    {
        return $this->response()->error(__('admin.deny'));
    }
}
