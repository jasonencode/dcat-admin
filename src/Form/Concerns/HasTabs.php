<?php

namespace Dcat\Admin\Form\Concerns;

use Closure;
use Dcat\Admin\Form\Tab;

trait HasTabs
{
    /**
     * @var Tab|null
     */
    protected ?Tab $tab = null;

    /**
     * Use tab to split form.
     *
     * @param  string  $title
     * @param  Closure  $content
     * @param  bool  $active
     * @param  string|null  $id
     * @return $this
     */
    public function tab(string $title, Closure $content, bool $active = false, ?string $id = null): static
    {
        $this->getTab()->append($title, $content, $active, $id);

        return $this;
    }

    public function hasTab(): bool
    {
        return (bool) $this->tab;
    }

    /**
     * Get Tab instance.
     *
     * @return Tab
     */
    public function getTab(): Tab
    {
        if (is_null($this->tab)) {
            $this->tab = new Tab($this);
        }

        return $this->tab;
    }
}
