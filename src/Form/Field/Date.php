<?php

namespace Dcat\Admin\Form\Field;

class Date extends Text
{
    public static $js = [
        '@moment',
        '@bootstrap-datetimepicker',
    ];
    public static $css = [
        '@bootstrap-datetimepicker',
    ];

    protected string $format = 'YYYY-MM-DD';

    public function format($format): static
    {
        $this->format = $format;

        return $this;
    }

    protected function prepareInputValue(mixed $value): mixed
    {
        if ($value === '') {
            $value = null;
        }

        return $value;
    }

    public function render(): string
    {
        $this->options['format'] = $this->format;
        $this->options['locale'] = config('app.locale');
        $this->options['allowInputToggle'] = true;

        $options = admin_javascript_json($this->options);

        $this->script = <<<JS
            Dcat.init('{$this->getElementClassSelector()}', function (self) {
                self.datetimepicker($options)
            });
            JS;

        $this->prepend('<i class="fa fa-calendar fa-fw"></i>')
            ->defaultAttribute('style', 'width: 200px;flex:none');

        return parent::render();
    }
}
