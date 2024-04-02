<?php

use Dcat\Admin\Admin;
use Dcat\Admin\Color;
use Dcat\Admin\Support\Helper;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;

if (! function_exists('admin_section')) {
    /**
     * Get the string contents of a section.
     *
     * @param  string  $section
     * @param  mixed|null  $default
     * @param  array  $options
     * @return mixed
     */
    function admin_section(string $section, mixed $default = null, array $options = []): mixed
    {
        return app('admin.sections')->yieldContent($section, $default, $options);
    }
}

if (! function_exists('admin_has_section')) {
    /**
     * Check if section exists.
     *
     * @param  string  $section
     * @return mixed
     */
    function admin_has_section(string $section): mixed
    {
        return app('admin.sections')->hasSection($section);
    }
}

if (! function_exists('admin_inject_section')) {
    /**
     * Injecting content into a section.
     *
     * @param  string  $section
     * @param  mixed|null  $content
     * @param  bool  $append
     * @param  int  $priority
     */
    function admin_inject_section(string $section, mixed $content = null, bool $append = true, int $priority = 10): void
    {
        app('admin.sections')->inject($section, $content, $append, $priority);
    }
}

if (! function_exists('admin_inject_section_if')) {
    /**
     * Injecting content into a section.
     *
     * @param  mixed  $condition
     * @param  string  $section
     * @param  mixed|null  $content
     * @param  bool  $append
     * @param  int  $priority
     */
    function admin_inject_section_if(mixed $condition, string $section, mixed $content = null, bool $append = false, int $priority = 10): void
    {
        if ($condition) {
            app('admin.sections')->inject($section, $content, $append, $priority);
        }
    }
}

if (! function_exists('admin_has_default_section')) {
    /**
     * Check if default section exists.
     *
     * @param  string  $section
     * @return mixed
     */
    function admin_has_default_section(string $section): mixed
    {
        return app('admin.sections')->hasDefaultSection($section);
    }
}

if (! function_exists('admin_inject_default_section')) {
    /**
     * Injecting content into a section.
     *
     * @param  string  $section
     * @param  callable|string|Htmlable|Renderable  $content
     */
    function admin_inject_default_section(string $section, callable|Renderable|Htmlable|string $content): void
    {
        app('admin.sections')->injectDefault($section, $content);
    }
}

if (! function_exists('admin_trans_field')) {
    /**
     * Translate the field name.
     *
     * @param $field
     * @param  null  $locale
     * @return array|\Illuminate\Contracts\Translation\Translator|null|string
     */
    function admin_trans_field($field, $locale = null): array|string|Translator|null
    {
        return app('admin.translator')->transField($field, $locale);
    }
}

if (! function_exists('admin_trans_label')) {
    /**
     * Translate the label.
     *
     * @param $label
     * @param  array  $replace
     * @param  null  $locale
     * @return array|\Illuminate\Contracts\Translation\Translator|null|string
     */
    function admin_trans_label($label = null, array $replace = [], $locale = null): array|string|Translator|null
    {
        return app('admin.translator')->transLabel($label, $replace, $locale);
    }
}

if (! function_exists('admin_trans_option')) {
    /**
     * Translate the field name.
     *
     * @param $optionValue
     * @param $field
     * @param  array  $replace
     * @param  null  $locale
     * @return array|\Illuminate\Contracts\Translation\Translator|null|string
     */
    function admin_trans_option($optionValue, $field, array $replace = [], $locale = null): array|string|Translator|null
    {
        $slug = admin_controller_slug();

        return admin_trans("$slug.options.$field.$optionValue", $replace, $locale);
    }
}

if (! function_exists('admin_trans')) {
    /**
     * Translate the given message.
     *
     * @param  string  $key
     * @param  array  $replace
     * @param  string|null  $locale
     * @return \Illuminate\Contracts\Translation\Translator|string|array|null
     */
    function admin_trans(string $key, array $replace = [], string $locale = null): array|string|Translator|null
    {
        return app('admin.translator')->trans($key, $replace, $locale);
    }
}

if (! function_exists('admin_controller_slug')) {
    /**
     * @return string
     */
    function admin_controller_slug(): string
    {
        static $slug = [];

        $controller = admin_controller_name();

        return $slug[$controller] ?? ($slug[$controller] = Helper::slug($controller));
    }
}

if (! function_exists('admin_controller_name')) {
    /**
     * Get the class "basename" of the current controller.
     *
     * @return string
     */
    function admin_controller_name(): string
    {
        return Helper::getControllerName();
    }
}

if (! function_exists('admin_path')) {
    /**
     * Get admin path.
     *
     * @param  string  $path
     * @return string
     */
    function admin_path(string $path = ''): string
    {
        return ucfirst(config('admin.directory')).($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('admin_url')) {
    /**
     * Get admin url.
     *
     * @param  string  $path
     * @param  mixed  $parameters
     * @param  bool|null  $secure
     * @return string
     */
    function admin_url(string $path = '', mixed $parameters = [], bool $secure = null): string
    {
        if (url()->isValidUrl($path)) {
            return $path;
        }

        $secure = $secure ?: (config('admin.https') || config('admin.secure'));

        return url(admin_base_path($path), $parameters, $secure);
    }
}

if (! function_exists('admin_base_path')) {
    /**
     * Get admin url.
     *
     * @param  string  $path
     * @return string
     */
    function admin_base_path(string $path = ''): string
    {
        $prefix = '/'.trim(config('admin.route.prefix'), '/');

        $prefix = ($prefix == '/') ? '' : $prefix;

        $path = trim($path, '/');

        if (empty($path) || strlen($path) == 0) {
            return $prefix ?: '/';
        }

        return $prefix.'/'.$path;
    }
}

if (! function_exists('admin_toastr')) {
    /**
     * Flash a toastr message bag to session.
     *
     * @param  string  $message
     * @param  string  $type
     * @param  array  $options
     */
    function admin_toastr(string $message = '', string $type = 'success', array $options = []): void
    {
        $toastr = new MessageBag(get_defined_vars());

        session()->flash('dcat-admin-toastr', $toastr);
    }
}

if (! function_exists('admin_success')) {
    /**
     * Flash a success message bag to session.
     *
     * @param  string  $title
     * @param  string  $message
     */
    function admin_success(string $title, string $message = ''): void
    {
        admin_info($title, $message, 'success');
    }
}

if (! function_exists('admin_error')) {
    /**
     * Flash a error message bag to session.
     *
     * @param  string  $title
     * @param  string  $message
     */
    function admin_error(string $title, string $message = ''): void
    {
        admin_info($title, $message, 'error');
    }
}

if (! function_exists('admin_warning')) {
    /**
     * Flash a warning message bag to session.
     *
     * @param  string  $title
     * @param  string  $message
     */
    function admin_warning(string $title, string $message = ''): void
    {
        admin_info($title, $message, 'warning');
    }
}

if (! function_exists('admin_info')) {
    /**
     * Flash a message bag to session.
     *
     * @param  string  $title
     * @param  string  $message
     * @param  string  $type
     */
    function admin_info(string $title, string $message = '', string $type = 'info'): void
    {
        $message = new MessageBag(get_defined_vars());

        session()->flash($type, $message);
    }
}

if (! function_exists('admin_asset')) {
    /**
     * @param $path
     * @return string
     */
    function admin_asset($path): string
    {
        return Admin::asset()->url($path);
    }
}

if (! function_exists('admin_route')) {
    /**
     * 根据路由别名获取url.
     *
     * @param  string|null  $route
     * @param  array  $params
     * @param  bool  $absolute
     * @return string
     */
    function admin_route(?string $route, array $params = [], bool $absolute = true): string
    {
        return Admin::app()->getRoute($route, $params, $absolute);
    }
}

if (! function_exists('admin_route_name')) {
    /**
     * 获取路由别名.
     *
     * @param  string|null  $route
     * @return string
     */
    function admin_route_name(?string $route): string
    {
        return Admin::app()->getRoutePrefix().$route;
    }
}

if (! function_exists('admin_api_route_name')) {
    /**
     * 获取api的路由别名.
     *
     * @param  string|null  $route
     * @return string
     */
    function admin_api_route_name(?string $route = ''): string
    {
        return Admin::app()->getCurrentApiRoutePrefix().$route;
    }
}

if (! function_exists('admin_color')) {
    /**
     * @param  string|null  $color
     * @return string|\Dcat\Admin\Color
     */
    function admin_color(?string $color = null): string|Color
    {
        if ($color === null) {
            return Admin::color();
        }

        return Admin::color()->get($color);
    }
}

if (! function_exists('admin_view')) {
    /**
     * @param  string  $view
     * @param  array  $data
     * @return string
     *
     * @throws \Throwable
     */
    function admin_view(string $view, array $data = []): string
    {
        return Admin::view($view, $data);
    }
}

if (! function_exists('admin_script')) {
    /**
     * @param $script
     * @param  bool  $direct
     * @return void
     */
    function admin_script($script, bool $direct = false): void
    {
        Admin::script($script, $direct);
    }
}

if (! function_exists('admin_style')) {
    /**
     * @param  string  $style
     * @return void
     */
    function admin_style(string $style): void
    {
        Admin::style($style);
    }
}

if (! function_exists('admin_js')) {
    /**
     * @param  array|string  $js
     * @return void
     */
    function admin_js(array|string $js): void
    {
        Admin::js($js);
    }
}

if (! function_exists('admin_css')) {
    /**
     * @param  array|string  $css
     * @return void
     */
    function admin_css(array|string $css): void
    {
        Admin::css($css);
    }
}

if (! function_exists('admin_require_assets')) {
    /**
     * @param  array|string  $asset
     * @return void
     */
    function admin_require_assets(array|string $asset): void
    {
        Admin::requireAssets($asset);
    }
}

if (! function_exists('admin_javascript')) {
    /**
     * 暂存JS代码，并使用唯一字符串代替.
     *
     * @param  string  $scripts
     * @return string
     */
    function admin_javascript(string $scripts): string
    {
        return Dcat\Admin\Support\JavaScript::make($scripts);
    }
}

if (! function_exists('admin_javascript_json')) {
    /**
     * @param  object|array  $data
     * @return string
     */
    function admin_javascript_json(object|array $data): string
    {
        return Dcat\Admin\Support\JavaScript::format($data);
    }
}

if (! function_exists('admin_exit')) {
    /**
     * 响应数据并中断后续逻辑.
     *
     * @param  array|string|Response|Renderable  $response
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    function admin_exit(array|string|Response|Renderable $response = ''): void
    {
        Admin::exit($response);
    }
}

if (! function_exists('admin_redirect')) {
    /**
     * 跳转.
     *
     * @param  string  $to
     * @param  int  $statusCode
     * @param  \Illuminate\Http\Request|null  $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector
     */
    function admin_redirect(
        string $to,
        int $statusCode = 302,
        Request $request = null
    ): \Illuminate\Http\Response|JsonResponse|Redirector|Application|RedirectResponse|ResponseFactory {
        return Helper::redirect($to, $statusCode, $request);
    }
}

if (! function_exists('format_byte')) {
    /**
     * 文件单位换算.
     *
     * @param  int|float  $input
     * @param  int  $dec
     * @return string
     */
    function format_byte(int|float $input, int $dec = 0): string
    {
        $prefix_arr = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value      = round($input, $dec);
        $i          = 0;
        while ($value > 1024) {
            $value /= 1024;
            $i++;
        }

        return round($value, $dec).$prefix_arr[$i];
    }
}
