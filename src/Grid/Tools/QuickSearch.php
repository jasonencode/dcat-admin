<?php

namespace Dcat\Admin\Grid\Tools;

use Dcat\Admin\Admin;
use Dcat\Admin\Support\Helper;
use Illuminate\Support\Traits\Macroable;

class QuickSearch extends AbstractTool
{
    use Macroable;

    /**
     * @var string
     */
    protected string $view = 'admin::grid.quick-search';

    /**
     * @var string|null
     */
    protected ?string $placeholder = null;

    /**
     * @var string
     */
    protected string $queryName = '_search_';

    /**
     * @var int rem
     */
    protected int $width = 18;

    /**
     * @var bool
     */
    protected bool $autoSubmit = true;

    /**
     * @return string
     */
    public function getQueryName(): string
    {
        return $this->parent->makeName($this->queryName);
    }

    /**
     * @param  int  $width
     * @return $this
     */
    public function width(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Set placeholder.
     *
     * @param  string|null  $text
     * @return $this
     */
    public function placeholder(?string $text = ''): static
    {
        $this->placeholder = $text;

        return $this;
    }

    /**
     * @return string
     */
    public function value(): string
    {
        return trim(request($this->getQueryName()) ?? '');
    }

    /**
     * @return string
     */
    public function formAction(): string
    {
        return Helper::fullUrlWithoutQuery([
            $this->getQueryName(),
            $this->parent->model()->getPageName(),
            '_pjax',
        ]);
    }

    /**
     * @param  bool  $value
     * @return $this
     */
    public function auto(bool $value = true): static
    {
        $this->autoSubmit = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $this->setupScript();

        $data = [
            'action'      => $this->formAction(),
            'key'         => $this->getQueryName(),
            'value'       => $this->value(),
            'placeholder' => $this->placeholder ?: trans('admin.search'),
            'width'       => $this->width,
            'auto'        => $this->autoSubmit,
        ];

        return view($this->view, $data);
    }

    protected function setupScript(): void
    {
        $script = <<<'JS'
(function () {
    var inputting = false,
        $ipt = $('input.quick-search-input'),
        val = $ipt.val(),
        ignoreKeys = [16, 17, 18, 20, 35, 36, 37, 38, 39, 40, 45, 144],
        auto = $ipt.attr('auto');

    var submit = Dcat.helpers.debounce(function (input) {
        inputting || $(input).parents('form').submit()
    }, 1200);

    function toggleBtn() {
        var t = $(this),
            btn = t.parent().parent().find('.quick-search-clear');

        if (t.val()) {
            btn.css({color: '#333', cursor: 'pointer'});
        } else {
            btn.css({color: '#fff', cursor: 'none'});
        }
        return false;
    }

    $ipt.on('focus', toggleBtn)
        .on('mousemove', toggleBtn)
        .on('mouseout', toggleBtn)
        .on('compositionstart', function(){
            inputting = true
        })
        .on('compositionend', function() {
            inputting = false
        });

    if (auto > 0) {
        $ipt.on('keyup', function (e) {
            toggleBtn.apply(this);

            ignoreKeys.indexOf(e.keyCode) === -1 && submit(this)
        })
    }

    val !== '' && $ipt.val('').focus().val(val);

    $('.quick-search-clear').on('click', function () {
        $(this).parent().find('.quick-search-input').val('');

        $(this).closest('form').submit();
    });
})()
JS;

        Admin::script($script);
    }
}
