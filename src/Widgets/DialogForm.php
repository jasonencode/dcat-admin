<?php

namespace Dcat\Admin\Widgets;

use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Support\Helper;
use Illuminate\Contracts\Support\Arrayable;

class DialogForm
{
    const QUERY_NAME = '_dialog_form_';

    /**
     * @var string
     */
    public static string $contentView = 'admin::layouts.form-content';

    /**
     * @var array
     */
    protected array $options = [
        'title' => 'Form',
        'area' => ['700px', '670px'],
        'defaultUrl' => null,
        'buttonSelector' => null,
        'query' => null,
        'lang' => null,
        'forceRefresh' => false,
        'resetButton' => true,
    ];

    /**
     * @var array
     */
    protected array $handlers = [
        'saved' => null,
        'success' => null,
        'error' => null,
    ];

    public function __construct(?string $title = null, $url = null)
    {
        $this->title($title);

        $this->url($url);
    }

    /**
     * @param  array  $options
     * @return $this
     */
    public function options(array $options = []): static
    {
        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * 设置弹窗标题.
     *
     * @param  string|null  $title
     * @return $this
     */
    public function title(?string $title): static
    {
        $this->options['title'] = $title;

        return $this;
    }

    /**
     * 绑定点击按钮.
     *
     * @param  string  $buttonSelector
     * @return $this
     */
    public function click(string $buttonSelector): static
    {
        $this->options['buttonSelector'] = $buttonSelector;

        return $this;
    }

    /**
     * 强制每次点击按钮都重新渲染表单弹窗.
     *
     * @return $this
     */
    public function forceRefresh(): static
    {
        $this->options['forceRefresh'] = true;

        return $this;
    }

    /**
     * 重置按钮.
     *
     * @param  bool  $value
     * @return $this
     */
    public function resetButton(bool $value = true): static
    {
        $this->options['resetButton'] = $value;

        return $this;
    }

    /**
     * 保存后触发的js的代码（不论成功还是失败）.
     *
     * @param  string  $script
     * @return $this
     */
    public function saved(string $script): static
    {
        $this->handlers['saved'] = $script;

        return $this;
    }

    /**
     * 保存失败时触发的js代码
     *
     * @param  string  $script
     * @return $this
     */
    public function error(string $script): static
    {
        $this->handlers['error'] = $script;

        return $this;
    }

    /**
     * 保存成功后触发的js代码
     *
     * @param  string  $script
     * @return $this
     */
    public function success(string $script): static
    {
        $this->handlers['success'] = $script;

        return $this;
    }

    /**
     * 设置弹窗宽高
     * 支持百分比和"px".
     *
     * @param  string  $width
     * @param  string  $height
     * @return $this
     */
    public function dimensions(string $width, string $height): static
    {
        $this->options['area'] = [$width, $height];

        return $this;
    }

    /**
     * 设置弹窗宽度
     * 支持百分比和"px".
     *
     * @param  string|null  $width
     * @return $this
     */
    public function width(?string $width): static
    {
        $this->options['area'][0] = $width;

        return $this;
    }

    /**
     * 设置弹窗高度
     * 支持百分比和"px".
     *
     * @param  string|null  $height
     * @return $this
     */
    public function height(?string $height): static
    {
        $this->options['area'][1] = $height;

        return $this;
    }

    /**
     * 设置默认的表单页面url.
     *
     * @param  null|string  $url
     * @return $this
     */
    public function url(?string $url): static
    {
        if ($url) {
            $this->options['defaultUrl'] = Helper::urlWithQuery(
                admin_url($url),
                [static::QUERY_NAME => 1]
            );
        }

        return $this;
    }

    /**
     * @return string
     */
    protected function render(): string
    {
        $this->setUpOptions();

        $opts = json_encode($this->options);

        Admin::script(
            <<<JS
                (function () {
                    var opts = $opts;
                
                    opts.success = function (success, response) {
                        {$this->handlers['success']}
                    };
                    opts.error = function (success, response) {
                        {$this->handlers['error']}
                    };
                    opts.saved = function (success, response) {
                        {$this->handlers['saved']}
                    };
                
                    Dcat.DialogForm(opts);
                })();
                JS
        );

        return '';
    }

    /**
     * 配置选项初始化.
     *
     * @return void
     */
    protected function setUpOptions(): void
    {
        $this->options['lang'] = [
            'submit' => trans('admin.submit'),
            'reset' => trans('admin.reset'),
        ];

        $this->options['query'] = static::QUERY_NAME;
    }

    /**
     * 判断是否是获取弹窗表单内容的请求
     *
     * @return bool
     */
    public static function is(): bool
    {
        return (bool) request(static::QUERY_NAME);
    }

    /**
     * @param  Form  $form
     */
    public static function prepare(Form $form): void
    {
        if (!static::is()) {
            return;
        }

        Admin::baseCss([], false);
        Admin::baseJs([], false);
        Admin::fonts(false);
        Admin::style('.form-content{ padding-top: 7px }');

        $form->wrap(function ($v) {
            return $v;
        });

        $form->disableHeader();
        $form->disableFooter();

        $form->width(9);

        $form->composing(function ($form) {
            static::addScript($form);
        });

        Content::composing(function (Content $content) {
            $content->view(static::$contentView);
        });
    }

    protected static function addScript(Form $form): void
    {
        $confirm = json_encode($form->builder()->confirm);

        Admin::script(
            <<<JS
                Dcat.FormConfirm = $confirm;
                JS
        );
    }

    public function __destruct()
    {
        if ($results = Helper::render($this->render())) {
            Admin::html($results);
        }
    }
}
