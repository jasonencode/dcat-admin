<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Support\Helper;
use Exception;

class MultipleFile extends File
{
    /**
     * Allow to sort files.
     *
     * @param  bool  $value
     * @return $this
     */
    public function sortable(bool $value = true): static
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
    public function limit(int $limit): static
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
     * @param  mixed  $value
     * @return array
     * @throws Exception
     */
    protected function prepareInputValue(mixed $value): array
    {
        if ($path = request(static::FILE_DELETE_FLAG)) {
            $this->deleteFile($path);

            return array_values(array_diff($this->original, [$path]));
        }

        $value = Helper::array($value);

        $this->destroyIfChanged($value);

        return $value;
    }

    protected function forceOptions(): void
    {
    }
}
