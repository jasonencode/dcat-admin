<?php

namespace Dcat\Admin\Form\Field;

use Closure;
use Dcat\Admin\Exception\AdminException;
use Dcat\Admin\Widgets\Form;

class Captcha extends Text
{
    protected Closure|array $rules = ['required', 'captcha'];

    protected string $view = 'admin::form.captcha';

    /**
     * @throws AdminException
     */
    public function __construct()
    {
        if (!class_exists(\Mews\Captcha\Captcha::class)) {
            throw new AdminException('To use captcha field, please install [mews/captcha] first.');
        }

        $this->column = '__captcha__';
        $this->label = trans('admin.captcha');
    }

    public function setForm(Form|\Dcat\Admin\Form $form = null): static
    {
        parent::setForm($form);

        if (method_exists($this->form, 'ignore')) {
            $this->form->ignore($this->column);
        }

        return $this;
    }

    public function render(): string
    {
        $this->addVariables(['captchaSrc' => captcha_src()]);

        return parent::render();
    }
}
