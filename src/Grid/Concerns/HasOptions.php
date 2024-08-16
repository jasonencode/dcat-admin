<?php

namespace Dcat\Admin\Grid\Concerns;

trait HasOptions
{
    /**
     * Get or set option for grid.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function option(string $key, mixed $value = null): static
    {
        $this->options[$key] = $value;
        return $this;
    }

    public function getOption(string $key): mixed
    {
        return $this->options[$key];
    }

    protected function setUpOptions(): void
    {
        if ($this->options['bordered']) {
            $this->tableCollapse(false);
        }
    }

    /**
     * @param  string|array  $class
     * @return void
     */
    public function addTableClass(string|array $class): void
    {
        $this->options['table_class'] = array_merge((array) $this->options['table_class'], (array) $class);
    }

    public function formatTableClass(): string
    {
        if ($this->options['bordered']) {
            $this->addTableClass(['table-bordered', 'complex-headers', 'data-table']);
        }

        return implode(' ', array_unique((array) $this->options['table_class']));
    }

    /**
     * Notes   : 设置对话框
     *
     * @Date   : 2024/8/16 13:54
     * @Author : <Jason.C>
     * @param  string  $width
     * @param  string  $height
     * @return $this
     */
    public function setDialogFormDimensions(string $width, string $height): static
    {
        return $this->option('dialog_form_area', [$width, $height]);
    }

    /**
     * @param  bool  $value
     * @return $this
     */
    public function withBorder(bool $value = true): static
    {
        return $this->option('bordered', $value);
    }

    /**
     * @param  bool  $value
     * @return $this
     */
    public function tableCollapse(bool $value = true): static
    {
        return $this->option('table_collapse', $value);
    }

    /**
     * 显示横轴滚动条.
     *
     * @param  bool  $value
     * @return $this
     */
    public function scrollbar(bool $value = true): static
    {
        return $this->option('table_scrollbar', $value);
    }

    /**
     * 是否显示横向滚动条.
     *
     * @param  bool  $value
     * @return $this
     */
    public function scrollbarX(bool $value = true): static
    {
        return $this->option('scrollbar_x', $value);
    }

    /**
     * Disable row selector.
     *
     * @param  bool  $disable
     * @return $this
     */
    public function disableRowSelector(bool $disable = true): static
    {
        $this->tools->disableBatchActions($disable);
        return $this->option('row_selector', ! $disable);
    }

    /**
     * Show row selector.
     *
     * @param  bool  $val
     * @return $this
     */
    public function showRowSelector(bool $val = true): static
    {
        return $this->disableRowSelector(! $val);
    }

    /**
     * Remove create button on grid.
     *
     * @param  bool  $disable
     * @return $this
     */
    public function disableCreateButton(bool $disable = true): static
    {
        return $this->option('create_button', ! $disable);
    }

    /**
     * Show create button.
     *
     * @param  bool  $val
     * @return $this
     */
    public function showCreateButton(bool $val = true): static
    {
        return $this->disableCreateButton(! $val);
    }

    /**
     * @return \Dcat\Admin\Grid|null
     */
    public function enableDialogCreate(): null|static
    {
        return $this->createMode(self::CREATE_MODE_DIALOG);
    }

    /**
     * If allow creation.
     *
     * @return bool
     */
    public function allowCreateButton(): bool
    {
        return $this->options['create_button'];
    }

    /**
     * @param  string  $mode
     * @return \Dcat\Admin\Grid|null
     */
    public function createMode(string $mode): null|static
    {
        return $this->option('create_mode', $mode);
    }
}
