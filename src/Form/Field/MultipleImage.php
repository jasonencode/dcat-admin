<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Support\Helper;

class MultipleImage extends Image
{
    protected $view = 'admin::form.file';

    /**
     * Allow to sort files.
     *
     * @param  bool  $value
     * @return $this
     */
    public function sortable(bool $value = true)
    {
        $this->options['sortable'] = $value;

        return $this;
    }

    /**
     * Set a limit of files.
     *
     * @param  int  $limit
     * @return $this
     */
    public function limit(int $limit)
    {
        if ($limit < 2) {
            return $this;
        }

        $this->options['fileNumLimit'] = $limit;

        return $this;
    }

    /**
     * Prepare for saving.
     *
     * @param  string|array  $value
     * @return array
     */
    protected function prepareInputValue($value)
    {
        if ($path = request(static::FILE_DELETE_FLAG)) {
            $this->deleteFile($path);

            return array_values(array_diff($this->original, [$path]));
        }

        $value = Helper::array($value, true);

        $this->destroyIfChanged($value);

        return $value;
    }

    protected function forceOptions(): void
    {
    }
}
