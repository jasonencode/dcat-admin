<?php

namespace Dcat\Admin\Form\Field;

use Closure;
use Dcat\Admin\Form\Field;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Widgets\Checkbox as WidgetCheckbox;
use Illuminate\Contracts\Support\Arrayable;

class Tree extends Field
{
    protected array $options = [
        'plugins' => ['checkbox', 'types'],
        'core' => [
            'check_callback' => true,

            'themes' => [
                'name' => 'proton',
                'responsive' => true,
            ],
        ],
        'checkbox' => [
            'keep_selected_style' => false,
            'three_state' => true,
        ],
        'types' => [
            'default' => [
                'icon' => false,
            ],
        ],
    ];

    protected array $nodes = [];

    protected array $parents = [];

    protected bool $expand = true;

    protected array $columnNames = [
        'id' => 'id',
        'text' => 'name',
        'parent' => 'parent_id',
    ];

    protected bool $exceptParents = true;

    protected bool $readOnly = false;

    protected int $rootParentId = 0;

    /**
     * @param  array|Closure|Arrayable  $data  exp:
     *                                          {
     *                                          "id": "1",
     *                                          "parent": "#",
     *                                          "text": "Dashboard",
     *                                          // "state": {"selected": true}
     *                                          }
     * @return $this
     */
    public function nodes(array|Closure|Arrayable $data): static
    {
        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }

        $this->nodes = &$data;

        return $this;
    }

    /**
     * 设置父级复选框是否禁止被单独选中.
     *
     * @param  bool  $value
     * @return $this
     */
    public function treeState(bool $value = true): static
    {
        $this->options['checkbox']['three_state'] = $value;

        return $this->exceptParentNode($value);
    }

    /**
     * 过滤父节点.
     *
     * @param  bool  $value
     * @return $this
     */
    public function exceptParentNode(bool $value = true): static
    {
        $this->exceptParents = $value;

        return $this;
    }

    public function rootParentId($id): static
    {
        $this->rootParentId = $id;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function readOnly(bool $value = true): static
    {
        $this->readOnly = true;

        return $this;
    }

    public function setIdColumn(string $name): static
    {
        $this->columnNames['id'] = $name;

        return $this;
    }

    public function setTitleColumn(string $name): static
    {
        $this->columnNames['text'] = $name;

        return $this;
    }

    public function setParentColumn(string $name): static
    {
        $this->columnNames['parent'] = $name;

        return $this;
    }

    protected function formatNodes(): void
    {
        $value = Helper::array($this->value());

        $this->value = &$value;

        if ($this->nodes instanceof Closure) {
            $this->nodes = Helper::array($this->nodes->call($this->values(), $value, $this));
        }

        if (!$this->nodes) {
            return;
        }

        $idColumn = $this->columnNames['id'];
        $textColumn = $this->columnNames['text'];
        $parentColumn = $this->columnNames['parent'];

        $parentIds = $nodes = [];

        foreach ($this->nodes as &$v) {
            if (empty($v[$idColumn])) {
                continue;
            }

            $parentId = $v[$parentColumn] ?? '#';
            if (empty($parentId) || $parentId == $this->rootParentId) {
                $parentId = '#';
            } else {
                $parentIds[] = $parentId;
            }

            $v['state'] = [];

            if ($value && in_array($v[$idColumn], $value)) {
                $v['state']['selected'] = true;
            }

            if ($this->readOnly) {
                $v['state']['disabled'] = true;
            }

            $nodes[] = [
                'id' => $v[$idColumn],
                'text' => $v[$textColumn] ?? null,
                'parent' => $parentId,
                'state' => $v['state'],
            ];
        }

        if ($this->exceptParents) {
            // 筛选出所有父节点，最终点击树节点时过滤掉父节点
            $this->parents = array_unique($parentIds);
        }

        $this->nodes = &$nodes;
    }

    /**
     * Set type.
     *
     * @param  array  $value
     * @return $this
     */
    public function type(array $value): static
    {
        $this->options['types'] = array_merge($this->options['types'], $value);

        return $this;
    }

    /**
     * Set plugins.
     *
     * @param  array  $value
     * @return $this
     */
    public function plugins(array $value): static
    {
        $this->options['plugins'] = $value;

        return $this;
    }

    /**
     * @param  bool  $value
     * @return $this
     */
    public function expand(bool $value = true): static
    {
        $this->expand = $value;

        return $this;
    }

    protected function formatFieldData(array $data): array
    {
        return Helper::array($this->getValueFromData($data));
    }

    protected function prepareInputValue(mixed $value): array
    {
        return Helper::array($value);
    }

    public function render(): string
    {
        $checkboxes = new WidgetCheckbox();

        $checkboxes->style('primary');
        $checkboxes->inline();
        $checkboxes->options([
            1 => trans('admin.selectall'),
            2 => trans('admin.expand'),
        ]);

        $this->readOnly && $checkboxes->disable(1);

        $this->expand && $checkboxes->check(2);

        $this->formatNodes();

        if ($v = $this->value()) {
            $this->attribute('value', implode(',', $v));
        }

        $this->addVariables([
            'checkboxes' => $checkboxes,
            'nodes' => $this->nodes,
            'expand' => $this->expand,
            'disabled' => empty($this->attributes['disabled']) ? '' : 'disabled',
            'parents' => $this->parents,
        ]);

        return parent::render();
    }
}
