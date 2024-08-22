<?php

namespace Dcat\Admin\Form;

use Closure;
use Dcat\Admin\Exception\RuntimeException;
use Dcat\Admin\Form;
use Dcat\Admin\Widgets\Form as WidgetForm;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;

/**
 * Class Row.
 *
 * @method Field\Text text($column, $label = '')
 * @method Field\Checkbox checkbox($column, $label = '')
 * @method Field\Radio radio($column, $label = '')
 * @method Field\Select select($column, $label = '')
 * @method Field\MultipleSelect multipleSelect($column, $label = '')
 * @method Field\Textarea textarea($column, $label = '')
 * @method Field\Hidden hidden($column, $label = '')
 * @method Field\Id id($column, $label = '')
 * @method Field\Ip ip($column, $label = '')
 * @method Field\Url url($column, $label = '')
 * @method Field\Email email($column, $label = '')
 * @method Field\Mobile mobile($column, $label = '')
 * @method Field\Slider slider($column, $label = '')
 * @method Field\Map map($latitude, $longitude, $label = '')
 * @method Field\Editor editor($column, $label = '')
 * @method Field\Date date($column, $label = '')
 * @method Field\Datetime datetime($column, $label = '')
 * @method Field\Time time($column, $label = '')
 * @method Field\Year year($column, $label = '')
 * @method Field\Month month($column, $label = '')
 * @method Field\DateRange dateRange($start, $end, $label = '')
 * @method Field\DateTimeRange datetimeRange($start, $end, $label = '')
 * @method Field\TimeRange timeRange($start, $end, $label = '')
 * @method Field\Number number($column, $label = '')
 * @method Field\Currency currency($column, $label = '')
 * @method Field\SwitchField switch ($column, $label = '')
 * @method Field\Display display($column, $label = '')
 * @method Field\Rate rate($column, $label = '')
 * @method Field\Divide divider()
 * @method Field\Password password($column, $label = '')
 * @method Field\Decimal decimal($column, $label = '')
 * @method Field\Html html($html, $label = '')
 * @method Field\Tags tags($column, $label = '')
 * @method Field\Icon icon($column, $label = '')
 * @method Field\Embeds embeds($column, $label = '')
 * @method Field\Captcha captcha()
 * @method Field\Listbox listbox($column, $label = '')
 * @method Field\File file($column, $label = '')
 * @method Field\Image image($column, $label = '')
 * @method Field\MultipleFile multipleFile($column, $label = '')
 * @method Field\MultipleImage multipleImage($column, $label = '')
 * @method Field\HasMany hasMany($column, $labelOrCallback, $callback = null)
 * @method Field\Tree tree($column, $label = '')
 * @method Field\Table table($column, $labelOrCallback, $callback = null)
 * @method Field\ListField list($column, $label = '')
 * @method Field\Timezone timezone($column, $label = '')
 * @method Field\KeyValue keyValue($column, $label = '')
 * @method Field\Tel tel($column, $label = '')
 * @method Field\Markdown markdown($column, $label = '')
 * @method Field\Range range($start, $end, $label = '')
 */
class Row implements Renderable
{
    /**
     * Callback for add field to current row.s.
     *
     * @var Closure
     */
    protected Closure $callback;

    /**
     * Parent form.
     *
     * @var Form|WidgetForm
     */
    protected Form|WidgetForm $form;

    /**
     * Fields in this row.
     *
     * @var Collection
     */
    protected Collection $fields;

    /**
     * Default field width for appended field.
     *
     * @var int
     */
    protected int $defaultFieldWidth = 12;

    /**
     * field width for appended field.
     *
     * @var int
     */
    protected int $fieldWidth = 12;

    /**
     * @var bool
     */
    protected bool $horizontal = false;

    /**
     * Row constructor.
     *
     * @param  Closure  $callback
     * @param  Form|WidgetForm  $form
     */
    public function __construct(Closure $callback, WidgetForm|Form $form)
    {
        $this->callback = $callback;
        $this->fields = collect();

        $this->form = $form;

        call_user_func($this->callback, $this);
    }

    /**
     * Get fields of this row.
     *
     * @return array|Collection
     */
    public function fields(): array|Collection
    {
        return $this->fields;
    }

    /**
     * If the form horizontal layout.
     *
     * @param  bool  $value
     * @return $this
     */
    public function horizontal(bool $value = true): static
    {
        $this->horizontal = $value;

        $this->fields->each->horizontal($value);

        return $this;
    }

    public function setFields(Collection $collection): static
    {
        $this->fields = $collection;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getKey(): mixed
    {
        return $this->form->getKey();
    }

    /**
     * @return Fluent|Model
     */
    public function model(): Model|Fluent
    {
        return $this->form->model();
    }

    /**
     * Set default width for field.
     *
     * @param  int  $width
     * @return $this
     */
    public function defaultWidth(int $width = 12): static
    {
        $this->defaultFieldWidth = $width;

        return $this;
    }

    /**
     * Set width for a incomming field.
     *
     * @param  int  $width
     * @return $this
     */
    public function width(int $width = 12): static
    {
        $this->fieldWidth = $width;

        return $this;
    }

    /**
     * Render the row.
     *
     * @return string
     */
    public function render(): string
    {
        return view('admin::form.row', ['fields' => $this->fields]);
    }

    /**
     * Add field.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return Field
     * @throws RuntimeException
     */
    public function __call(string $method, array $arguments)
    {
        $field = $this->form->__call($method, $arguments);

        $field->horizontal($this->horizontal);

        $this->fields->push([
            'width' => $this->fieldWidth,
            'element' => $field,
        ]);

        $this->fieldWidth = $this->defaultFieldWidth; // reset field width for next field

        return $field;
    }
}
