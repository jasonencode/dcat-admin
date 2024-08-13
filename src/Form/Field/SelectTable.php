<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Form\Field;
use Dcat\Admin\Grid\LazyRenderable;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Widgets\DialogTable;

class SelectTable extends Field
{
    use PlainInput;
    use CanLoadFields;

    /**
     * @var DialogTable
     */
    protected $dialog;

    protected string $style = 'primary';

    protected $visibleColumn;

    protected $key;

    public function __construct($column, $arguments = [])
    {
        parent::__construct($column, $arguments);

        $this->dialog = DialogTable::make($this->label);
    }

    /**
     * 设置弹窗标题.
     *
     * @param  string  $title
     * @return $this
     */
    public function title(string $title): static
    {
        $this->dialog->title($title);

        return $this;
    }

    /**
     * 设置弹窗宽度.
     *
     * @param  string  $width
     * @return $this
     * @example
     *    $this->width('500px');
     *    $this->width('50%');
     *
     */
    public function dialogWidth(string $width): static
    {
        $this->dialog->width($width);

        return $this;
    }

    /**
     * Show Max or Min icon.
     *
     * @param  bool  $maxmin
     * @return $this
     */
    public function dialogMaxMin(bool $maxmin): static
    {
        $this->dialog->maxmin($maxmin);

        return $this;
    }

    /**
     * Resize.
     *
     * @param  bool  $resize
     * @return $this
     */
    public function dialogResize(bool $resize): static
    {
        $this->dialog->resize($resize);

        return $this;
    }

    /**
     * 设置表格异步渲染实例.
     *
     * @param  LazyRenderable  $renderable
     * @return $this
     */
    public function from(LazyRenderable $renderable): static
    {
        $this->dialog->from($renderable);

        return $this;
    }

    /**
     * 设置选中的key以及标题字段.
     *
     * @param  string|null  $visibleColumn
     * @param  string|null  $key
     * @return $this
     */
    public function pluck(?string $visibleColumn, ?string $key = 'id'): static
    {
        $this->visibleColumn = $visibleColumn;
        $this->key           = $key;

        return $this;
    }

    /**
     * @param  array  $options
     * @return $this
     */
    public function options($options = []): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * 设置选中数据显示.
     *
     * @param  string  $model
     * @param  string  $id
     * @param  string  $text
     * @return $this
     */
    public function model(string $model, string $id = 'id', string $text = 'title'): static
    {
        return $this->pluck($text, $id)->options(function ($v) use ($model, $id, $text) {
            if (! $v) {
                return [];
            }

            return $model::whereIn($id, Helper::array($v))->pluck($text, $id);
        });
    }

    protected function formatOptions(): void
    {
        $value = Helper::array($this->value());

        if ($this->options instanceof \Closure) {
            $this->options = $this->options->call($this->values(), $value, $this);
        }

        $values = [];

        foreach (Helper::array($this->options) as $id => $label) {
            foreach ($value as $v) {
                if ($v == $id && $v !== null) {
                    $values[] = ['id' => $v, 'label' => $label];
                }
            }
        }

        $this->options = $values;
    }

    /**
     * @return string
     */
    protected function defaultPlaceholder(): string
    {
        return trans('admin.choose').' '.$this->label;
    }

    protected function setUpTable(): void
    {
        $this->dialog
            ->footer($this->renderFooter())
            ->button($this->renderButton());

        // 设置选中的字段和待显示的标题字段
        $this->dialog
            ->getTable()
            ->getRenderable()
            ->payload([
                LazyRenderable::ROW_SELECTOR_COLUMN_NAME => [$this->key, $this->visibleColumn],
            ]);
    }

    public function render(): string
    {
        $this->setUpTable();
        $this->formatOptions();

        $this->prepend('<i class="feather icon-arrow-up"></i>')
            ->defaultAttribute('class', 'form-control '.$this->getElementClassString())
            ->defaultAttribute('type', 'text')
            ->defaultAttribute('name', $this->getElementName());

        $this->addVariables([
            'prepend'        => $this->prepend,
            'append'         => $this->append,
            'style'          => $this->style,
            'dialog'         => $this->dialog->render(),
            'placeholder'    => $this->placeholder(),
            'dialogSelector' => $this->dialog->getElementSelector(),
        ]);

        return parent::render();
    }

    protected function renderButton(): string
    {
        return <<<HTML
<div class="btn btn-{$this->style}">
    &nbsp;<i class="feather icon-arrow-up"></i>&nbsp;
</div>
HTML;
    }

    /**
     * 弹窗底部内容构建.
     *
     * @return string
     */
    protected function renderFooter(): string
    {
        $submit = trans('admin.submit');
        $cancel = trans('admin.cancel');

        return <<<HTML
<button class="btn btn-primary btn-sm submit-btn" style="color: #fff">&nbsp;{$submit}&nbsp;</button>&nbsp;
<button  class="btn btn-white btn-sm cancel-btn">&nbsp;{$cancel}&nbsp;</button>
HTML;
    }
}
