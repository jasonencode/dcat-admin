<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Models\Operation;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\JsonResponse;
use Dcat\Admin\Support\Helper;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class OperationController extends AdminController
{
    protected string $title = '操作日志';

    public function grid(): Grid
    {
        return Grid::make(Operation::class, function (Grid $grid) {
            $grid->model()->orderBy('id', 'DESC');
            $grid->disableCreateButton();
            $grid->disableQuickEditButton();
            $grid->disableEditButton();
            $grid->disableViewButton();
            $grid->showColumnSelector();
            $grid->setActionClass(Grid\Displayers\Actions::class);

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('user.username', '用户');
                $filter->equal('method', trans('admin.method'))
                    ->select(
                        array_combine(Operation::$methods, Operation::$methods)
                    );
                $filter->like('path', trans('admin.uri'));
                $filter->equal('ip', 'IP');
                $filter->between('created_at')->datetime();
            });

            $grid->column('id', 'ID')->sortable();
            $grid->column('user')
                ->display(function ($user) {
                    if (! $user) {
                        return '';
                    }
                    $user = Helper::array($user);
                    return $user['name'] ?? ($user['username'] ?? $user['id']);
                });
            $grid->column('method', trans('admin.method'));
            $grid->column('path', trans('admin.uri'))->display(function ($v) {
                return "<code>$v</code>";
            });
            $grid->column('ip', 'IP');
            $grid->column('input')->display(function ($input) {
                $input = json_decode($input, true);
                if (empty($input)) {
                    return '';
                }
                $input = Arr::except($input, ['_pjax', '_token', '_method', '_previous_']);
                return '<pre class="dump" style="max-width: 500px">'.json_encode($input,
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'</pre>';
            });
            $grid->column('created_at', trans('admin.created_at'));
        });
    }

    public function destroy($id): Response
    {
        $ids = explode(',', $id);

        Operation::destroy(array_filter($ids));

        return JsonResponse::make()
            ->success(trans('admin.delete_succeeded'))
            ->refresh()
            ->send();
    }
}