<?php

namespace Dcat\Admin\Grid\Filter;

use Closure;
use Dcat\Admin\Admin;
use Dcat\Admin\Grid\Filter;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Group extends AbstractFilter
{
    /**
     * @var Closure|null
     */
    protected ?Closure $builder;

    /**
     * @var string
     */
    protected string $name;

    /**
     * Input value from presenter.
     *
     * @var mixed
     */
    public mixed $input;

    /**
     * Group constructor.
     *
     * @param  string  $column
     * @param  string  $label
     * @param  Closure|null  $builder
     */
    public function __construct(string $column, Closure $builder = null, string $label = '')
    {
        $this->builder = $builder;
        $this->column = $column;
        $this->label = $this->formatLabel($label);
    }

    /**
     * @param  Filter  $filter
     */
    public function setParent(Filter $filter): void
    {
        parent::setParent($filter);

        $this->initialize();
    }

    /**
     * Initialize a group filter.
     */
    protected function initialize(): void
    {
        $this->group = new Collection();
        $this->name = "$this->id-filter-group";
    }

    /**
     * Join a query to group.
     *
     * @param  string  $label
     * @param  array  $condition
     * @return $this
     */
    protected function joinGroup(string $label, array $condition): static
    {
        $this->group->push(
            compact('label', 'condition')
        );

        return $this;
    }

    /**
     * Filter out `equal` records.
     *
     * @param  string  $label
     * @param  string  $operator
     * @return $this
     */
    public function equal(string $label = '', string $operator = '='): static
    {
        $label = $label ?: $operator;

        $condition = [$this->column, $operator, $this->value];

        return $this->joinGroup($label, $condition);
    }

    /**
     * Filter out `not equal` records.
     *
     * @param  string  $label
     * @return Group
     */
    public function notEqual(string $label = ''): static
    {
        return $this->equal($label, '!=');
    }

    /**
     * Filter out `greater then` records.
     *
     * @param  string  $label
     * @return Group
     */
    public function gt(string $label = ''): static
    {
        return $this->equal($label, '>');
    }

    /**
     * Filter out `less then` records.
     *
     * @param  string  $label
     * @return Group
     */
    public function lt(string $label = ''): static
    {
        return $this->equal($label, '<');
    }

    /**
     * Filter out `not less then` records.
     *
     * @param  string  $label
     * @return Group
     */
    public function nlt(string $label = ''): static
    {
        return $this->equal($label, '>=');
    }

    /**
     * Filter out `not greater than` records.
     *
     * @param  string  $label
     * @return Group
     */
    public function ngt(string $label = ''): static
    {
        return $this->equal($label, '<=');
    }

    /**
     * Filter out records that match the regex.
     *
     * @param  string  $label
     * @return Group
     */
    public function match(string $label = ''): static
    {
        $label = $label ?: 'Match';

        return $this->equal($label, 'REGEXP');
    }

    /**
     * Specify a where query.
     *
     * @param  string  $label
     * @param  Closure  $builder
     * @return Group
     */
    public function where(string $label, Closure $builder): static
    {
        $this->input = $this->value;

        $condition = [$builder->bindTo($this)];

        return $this->joinGroup($label, $condition);
    }

    /**
     * Specify a where like query.
     *
     * @param  string  $label
     * @param  string  $operator
     * @return Group
     */
    public function like(string $label = '', string $operator = 'like'): static
    {
        $label = $label ?: $operator;

        $condition = [$this->column, $operator, "%$this->value%"];

        return $this->joinGroup($label, $condition);
    }

    /**
     * Alias of `like` method.
     *
     * @param  string  $label
     * @return Group
     */
    public function contains(string $label = ''): static
    {
        return $this->like($label);
    }

    /**
     * Specify a where ilike query.
     *
     * @param  string  $label
     * @return Group
     */
    public function ilike(string $label = ''): static
    {
        return $this->like($label, 'ilike');
    }

    /**
     * Filter out records which starts with input query.
     *
     * @param  string  $label
     * @return Group
     */
    public function startWith(string $label = ''): static
    {
        $label = $label ?: 'Start with';

        $condition = [$this->column, 'like', "$this->value%"];

        return $this->joinGroup($label, $condition);
    }

    /**
     * Filter out records which ends with input query.
     *
     * @param  string  $label
     * @return Group
     */
    public function endWith(string $label = ''): static
    {
        $label = $label ?: 'End with';

        $condition = [$this->column, 'like', "%$this->value"];

        return $this->joinGroup($label, $condition);
    }

    /**
     * {@inheritdoc}
     */
    public function condition(array $inputs)
    {
        $value = Arr::get($inputs, $this->column);

        if (!isset($value)) {
            return;
        }

        $this->value = $value;

        $group = Arr::get($inputs, "{$this->id}_group");

        if ($this->group->isEmpty()) {
            call_user_func($this->builder, $this);
        }

        if ($query = $this->group->get($group)) {
            return $this->buildCondition(...$query['condition']);
        }
    }

    /**
     * Inject script to current page.
     */
    protected function injectScript(): void
    {
        $script = <<<JS
            $(".$this->name li a").on('click', function(){
                $(".$this->name-label").text($(this).text());
                $(".$this->name-operation").val($(this).data('index'));
            });
            JS;

        Admin::script($script);
    }

    /**
     * {@inheritdoc}
     */
    public function defaultVariables(): array
    {
        $select = request("{$this->id}_group");

        $default = $this->group->get($select) ?: $this->group->first();

        return array_merge(parent::defaultVariables(), [
            'group_name' => $this->name,
            'default' => $default,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function render(): string
    {
        $this->injectScript();

        if ($this->builder && $this->group->isEmpty()) {
            call_user_func($this->builder, $this);
        }

        return parent::render();
    }
}
