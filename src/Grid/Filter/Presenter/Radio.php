<?php

namespace Dcat\Admin\Grid\Filter\Presenter;

use Illuminate\Contracts\Support\Arrayable;

class Radio extends Presenter
{
    /**
     * @var array
     */
    protected array $options = [];

    /**
     * Display inline.
     *
     * @var bool
     */
    protected bool $inline = true;

    /**
     * @var bool
     */
    protected bool $showLabel = true;

    /**
     * Radio constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        $this->options = $options;

        return $this;
    }

    /**
     * Draw stacked radios.
     *
     * @return $this
     */
    public function stacked(): self
    {
        $this->inline = false;

        return $this;
    }

    public function showLabel(bool $value): static
    {
        $this->showLabel = $value;

        return $this;
    }

    protected function prepare()
    {
    }

    /**
     * @return array
     */
    public function defaultVariables(): array
    {
        $this->prepare();

        return [
            'options'   => $this->options,
            'inline'    => $this->inline,
            'showLabel' => $this->showLabel,
        ];
    }
}
