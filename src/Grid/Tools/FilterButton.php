<?php

namespace Dcat\Admin\Grid\Tools;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid\Filter;
use Illuminate\Support\Str;
use Throwable;

class FilterButton extends AbstractTool
{
    /**
     * @var string
     */
    protected string $view = 'admin::filter.button';

    /**
     * @var string
     */
    protected string $btnClassName = '';

    /**
     * @return Filter
     */
    protected function filter(): Filter
    {
        return $this->parent->filter();
    }

    /**
     * Get button class name.
     *
     * @return string
     */
    protected function getElementClassName(): string
    {
        if (!$this->btnClassName) {
            $this->btnClassName = 'filter-btn-'.Str::random(8);
        }

        return $this->btnClassName;
    }

    /**
     * Set up script for filter button.
     */
    protected function addScript(): void
    {
        $filter = $this->filter();
        $id = $filter->filterID();

        if ($filter->mode() === Filter::MODE_RIGHT_SIDE) {
            if ($filter->grid()->model()->getCurrentPage() > 1) {
                $expand = 'false';
            } else {
                $expand = $filter->expand ? 'true' : 'false';
            }

            $script = <<<JS
                (function () {
                    var slider,
                        expand = $expand;

                     function initSlider() {
                        slider = new Dcat.Slider({
                            target: '#$id',
                        });

                        slider
                            .\$container
                            .find('.right-side-filter-container .header')
                            .width(slider.\$container.width() - 20);

                        expand && setTimeout(slider.open.bind(slider), 10);
                    }

                    expand && setTimeout(initSlider, 10);

                    $('.{$this->getElementClassName()}').on('click', function () {
                        if (! slider) {
                            initSlider()
                        }
                        slider.toggle();

                        return false
                    });

                    $('.wrapper').on('click', '.modal', function (e) {
                        if (typeof e.cancelBubble != "undefined") {
                            e.cancelBubble = true;
                        }
                        if (typeof e.stopPropagation != "undefined") {
                            e.stopPropagation();
                        }
                    });
                    $(document).on('click', '.wrapper', function (e) {
                        if (slider && slider.close) {
                            slider.close();
                        }
                    });
                })();
                JS;
        } else {
            $script = <<<JS
                $('.{$this->getElementClassName()}').on('click', function(){
                    $('#$id').parent().toggleClass('d-none');
                });
                JS;
        }

        Admin::script($script);
    }

    /**
     * @return mixed
     */
    protected function renderScopes(): mixed
    {
        return $this->filter()->scopes()->map->render()->implode("\r\n");
    }

    /**
     * Get label of current scope.
     *
     * @return string
     */
    protected function currentScopeLabel(): string
    {
        if ($scope = $this->filter()->getCurrentScope()) {
            return "&nbsp;{$scope->getLabel()}&nbsp;";
        }

        return '';
    }

    /**
     * {@inheritdoc}
     * @throws Throwable
     */
    public function render(): string
    {
        $filter = $this->filter();

        $scopres = $filter->scopes();
        $filters = $filter->filters();
        $valueCount = $filter->countConditions();

        if ($scopres->isEmpty() && !$filters) {
            return '';
        }

        $this->addScript();

        $onlyScopes = (!$filters || $this->parent->getOption('filter') === false) && !$scopres->isEmpty();

        $variables = [
            'scopes' => $scopres,
            'current_label' => $this->currentScopeLabel(),
            'url_no_scopes' => $filter->urlWithoutScopes(),
            'btn_class' => $this->getElementClassName(),
            'expand' => $filter->expand,
            'filter_text' => true,
            'only_scopes' => $onlyScopes,
            'valueCount' => $valueCount,
        ];

        return view($this->view, $variables)->render();
    }
}
