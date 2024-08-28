<?php

namespace Dcat\Admin\Form\Concerns;

use Closure;
use Dcat\Admin\Form\Field;

trait HandleCascadeFields
{
    /**
     * @param  array  $dependency
     * @param  Closure  $closure
     * @return Field\CascadeGroup
     */
    public function cascadeGroup(Closure $closure, array $dependency): Field\CascadeGroup
    {
        $this->pushField($group = new Field\CascadeGroup($dependency));

        call_user_func($closure, $this);

        $this->html($group->end())->plain();

        return $group;
    }
}
