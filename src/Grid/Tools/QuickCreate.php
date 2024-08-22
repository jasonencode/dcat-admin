<?php

namespace Dcat\Admin\Grid\Tools;

use Dcat\Admin\Admin;
use Dcat\Admin\Form\Field;
use Dcat\Admin\Form\Field\MultipleSelect;
use Dcat\Admin\Form\Field\Text;
use Dcat\Admin\Grid;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use Throwable;

class QuickCreate implements Renderable
{
    use Macroable;

    /**
     * @var Grid
     */
    protected Grid $parent;

    /**
     * @var Collection
     */
    protected Collection $fields;

    /**
     * @var string
     */
    protected string $action;

    /**
     * @var string
     */
    protected string $method = 'POST';

    /**
     * QuickCreate constructor.
     *
     * @param  Grid  $grid
     */
    public function __construct(Grid $grid)
    {
        $this->parent = $grid;
        $this->fields = Collection::make();
        $this->action = request()->url();
    }

    protected function formatPlaceholder($placeholder): array
    {
        return array_filter((array) $placeholder);
    }

    /**
     * @param  string  $column
     * @param  string  $placeholder
     * @return Text
     */
    public function text(string $column, string $placeholder = ''): Text
    {
        $field = new Text($column, $this->formatPlaceholder($placeholder));

        $this->addField($field->attribute('style', 'width:180px'));

        return $field;
    }

    /**
     * @param  string  $column
     * @return Text
     */
    public function hidden(string $column): Text
    {
        return $this->text($column)
            ->attribute('hidden', 'hidden');
    }

    /**
     * @param  string  $column
     * @param  string  $placeholder
     * @return Text
     */
    public function email(string $column, string $placeholder = ''): Text
    {
        return $this->text($column, $placeholder)
            ->inputmask(['alias' => 'email']);
    }

    /**
     * @param  string  $column
     * @param  string  $placeholder
     * @return Text
     */
    public function ip(string $column, string $placeholder = ''): Text
    {
        return $this->text($column, $placeholder)
            ->inputmask(['alias' => 'ip'])
            ->attribute('style', 'width:120px');
    }

    /**
     * @param  string  $column
     * @param  string  $placeholder
     * @return Text
     */
    public function url(string $column, string $placeholder = ''): Text
    {
        return $this->text($column, $placeholder)
            ->inputmask(['alias' => 'url']);
    }

    /**
     * @param  string  $column
     * @param  string  $placeholder
     * @return Text
     */
    public function password(string $column, string $placeholder = ''): Text
    {
        return $this->text($column, $placeholder)
            ->attribute('type', 'password')
            ->attribute('style', 'width:120px');
    }

    /**
     * @param  string  $column
     * @param  string  $placeholder
     * @return Text
     */
    public function mobile(string $column, string $placeholder = ''): Text
    {
        return $this->text($column, $placeholder)
            ->inputmask(['mask' => '99999999999'])
            ->attribute('style', 'width:120px');
    }

    /**
     * @param  string  $column
     * @param  string  $placeholder
     * @return Text
     */
    public function integer(string $column, string $placeholder = ''): Text
    {
        return $this->text($column, $placeholder)
            ->inputmask(['alias' => 'integer'])
            ->attribute('style', 'width:150px');
    }

    /**
     * @param  string  $column
     * @param  string  $placeholder
     * @return Field\Select
     */
    public function select(string $column, string $placeholder = ''): Field\Select
    {
        $field = new Field\Select($column, $this->formatPlaceholder($placeholder));

        $this->addField($field);

        return $field;
    }

    /**
     * @param  string  $column
     * @param  string  $placeholder
     * @return Field\Tags
     */
    public function tags(string $column, string $placeholder = ''): Field\Tags
    {
        $field = new Field\Tags($column, $this->formatPlaceholder($placeholder));

        $this->addField($field);

        return $field;
    }

    /**
     * @param  string  $column
     * @param  string  $placeholder
     * @return MultipleSelect
     */
    public function multipleSelect(string $column, string $placeholder = ''): MultipleSelect
    {
        $field = new MultipleSelect($column, $this->formatPlaceholder($placeholder));

        $this->addField($field);

        return $field;
    }

    /**
     * @param  string  $column
     * @param  string  $placeholder
     * @return Field\Date
     */
    public function datetime(string $column, string $placeholder = ''): Field\Date
    {
        return $this->date($column, $placeholder)->format('YYYY-MM-DD HH:mm:ss');
    }

    /**
     * @param  string  $column
     * @param  string  $placeholder
     * @return Field\Date
     */
    public function time(string $column, string $placeholder = ''): Field\Date
    {
        return $this->date($column, $placeholder)->format('HH:mm:ss');
    }

    /**
     * @param  string  $column
     * @param  string  $placeholder
     * @return Field\Date
     */
    public function date(string $column, string $placeholder = ''): Field\Date
    {
        $field = new Field\Date($column, $this->formatPlaceholder($placeholder));

        $this->addField($field);

        return $field;
    }

    /**
     * @param  Field  $field
     * @return Field
     */
    protected function addField(Field $field): Field
    {
        $elementClass = array_merge([$this->getElementClass()], $field->getElementClass());

        $field->addElementClass($elementClass);

        $field->setView($this->resolveView(get_class($field)));

        $field::requireAssets();

        $this->fields->push($field);

        return $field;
    }

    /**
     * @param  string  $class
     * @return string
     */
    protected function resolveView(string $class): string
    {
        $path = explode('\\', $class);

        $name = strtolower(array_pop($path));

        return "admin::grid.quick-create.$name";
    }

    /**
     * @param  string|null  $action
     * @return $this
     */
    public function action(?string $action): static
    {
        $this->action = admin_url($action);

        return $this;
    }

    /**
     * @param  string|null  $method
     * @return $this
     */
    public function method(?string $method = 'POST'): static
    {
        $this->method = $method;

        return $this;
    }

    public function getElementClass(): string
    {
        return $this->parent->makeName('quick-create');
    }

    /**
     * @param  int  $columnCount
     * @return string
     * @throws Throwable
     */
    public function render(int $columnCount = 0): string
    {
        if ($this->fields->isEmpty()) {
            return '';
        }

        $vars = [
            'columnCount' => $columnCount,
            'fields' => $this->fields,
            'elementClass' => $this->getElementClass(),
            'url' => $this->action,
            'method' => $this->method,
            'uniqueName' => $this->parent->getName(),
        ];

        return Admin::view('admin::grid.quick-create.form', $vars);
    }
}
