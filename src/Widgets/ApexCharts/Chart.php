<?php

namespace Dcat\Admin\Widgets\ApexCharts;

use Dcat\Admin\Support\Helper;
use Dcat\Admin\Support\JavaScript;
use Dcat\Admin\Traits\InteractsWithApi;
use Dcat\Admin\Widgets\Widget;
use Illuminate\Support\Str;

class Chart extends Widget
{
    use InteractsWithApi;

    public static array $js = [
        '@apex-charts',
    ];

    protected $containerSelector;

    protected bool $built = false;

    public function __construct($selector = null, $options = [])
    {
        if ($selector && ! is_string($selector)) {
            $options  = $selector;
            $selector = null;
        }

        $this->selector($selector);

        $this->options($options);
    }

    /**
     * 设置或获取图表容器选择器.
     *
     * @param  string|null  $selector
     * @return $this
     */
    public function selector(?string $selector = null): static
    {
        if ($selector === null) {
            return $this->containerSelector;
        }

        $this->containerSelector = $selector;

        if ($selector && ! $this->built) {
            $this->autoRender();
        }

        return $this;
    }

    /**
     * @param  array|string  $title
     * @return $this
     */
    public function title(array|string $title): static
    {
        if (is_string($title)) {
            $options = ['text' => $title];
        } else {
            $options = Helper::array($title);
        }

        $this->options['title'] = $options;

        return $this;
    }

    /**
     * @param  array  $series
     * @return $this
     */
    public function series(array $series): static
    {
        $this->options['series'] = Helper::array($series);

        return $this;
    }

    /**
     * @param  array  $value
     * @return $this
     */
    public function labels(array $value): static
    {
        $this->options['labels'] = Helper::array($value);

        return $this;
    }

    /**
     * @param  array|string  $colors
     * @return $this
     */
    public function colors(array|string $colors): static
    {
        $this->options['colors'] = Helper::array($colors);

        return $this;
    }

    /**
     * @param  array  $value
     * @return $this
     */
    public function stroke(array $value): static
    {
        $this->options['stroke'] = Helper::array($value);

        return $this;
    }

    /**
     * @param  array  $value
     * @return $this
     */
    public function xaxis(array $value): static
    {
        $this->options['xaxis'] = Helper::array($value);

        return $this;
    }

    /**
     * @param  array  $value
     * @return $this
     */
    public function tooltip(array $value): static
    {
        $this->options['tooltip'] = Helper::array($value);

        return $this;
    }

    /**
     * @param  array  $value
     * @return $this
     */
    public function yaxis(array $value): static
    {
        $this->options['yaxis'] = Helper::array($value);

        return $this;
    }

    /**
     * @param  array  $value
     * @return $this
     */
    public function fill(array $value): static
    {
        $this->options['fill'] = Helper::array($value);

        return $this;
    }

    /**
     * @param  array  $value
     * @return $this
     */
    public function chart(array $value): static
    {
        $this->options['chart'] = Helper::array($value);

        return $this;
    }

    /**
     * @param  bool|array  $value
     * @return $this
     */
    public function dataLabels(bool|array $value): static
    {
        if (is_bool($value)) {
            $value = ['enabled' => $value];
        }

        $this->options['dataLabels'] = Helper::array($value);

        return $this;
    }

    /**
     * @return string
     */
    protected function buildDefaultScript(): string
    {
        $options = JavaScript::format($this->options);

        return <<<JS
(function () {
    var options = $options;

    var chart = new ApexCharts(
        $("$this->containerSelector")[0], 
        options
    );
    chart.render();
})();
JS;
    }

    /**
     * @return string
     */
    public function addScript(): string
    {
        if (! $this->allowBuildRequest()) {
            return $this->script = $this->buildDefaultScript();
        }

        $this->fetched(
            <<<JS
if (! response.status) {
    return Dcat.error(response.message || 'Server internal error.');
}

var chartBox = $(response.selector || '$this->containerSelector');

if (chartBox.length) {
    chartBox.html('');

    if (typeof response.options === 'string') {
        eval(response.options);
    }
    
    setTimeout(function () {
        new ApexCharts(chartBox[0], response.options).render();
    }, 50);
}
JS
        );

        return $this->script = $this->buildRequestScript();
    }

    /**
     * @return string
     * @throws \Throwable
     */
    public function render(): string
    {
        if ($this->built) {
            return '';
        }
        $this->built = true;

        return parent::render();
    }

    public function html(): string
    {
        $hasSelector = (bool) $this->containerSelector;

        if (! $hasSelector) {
            // 没有指定ID，需要自动生成
            $id = $this->generateId();

            $this->selector('#'.$id);
        }

        $this->addScript();

        if ($hasSelector) {
            return '';
        }

        // 没有指定容器选择器，则需自动生成
        $this->setHtmlAttribute([
            'id' => $id,
        ]);

        return <<<HTML
<div {$this->formatHtmlAttributes()}></div>
HTML;
    }

    /**
     * 返回API请求结果.
     *
     * @return array
     */
    public function valueResult(): array
    {
        return [
            'status'   => 1,
            'selector' => $this->containerSelector,
            'options'  => $this->formatScriptOptions(),
        ];
    }

    /**
     * 配置选项转化为JS可执行代码.
     *
     * @return string
     */
    protected function formatScriptOptions(): string
    {
        $code = JavaScript::format($this->options);

        return "response.options = $code";
    }

    /**
     * 生成唯一ID.
     *
     * @return string
     */
    protected function generateId(): string
    {
        return 'apex-chart-'.Str::random(8);
    }
}
