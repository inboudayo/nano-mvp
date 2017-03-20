<?php

namespace framework;

class Router
{
    // controller dependencies
    use \GlobalRepository;
    private $model;
    private $view;

    // default values, if nothing is specified
    private $controller  = 'framework\controllers\IndexController';
    private $method      = 'index';
    private $params      = [];

    // route request to the appropriate controller / method
    public function route()
    {
        // get request path
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (BASE_PATH != '/') {
            $path = trim(substr($path, strlen(BASE_PATH)), '/');
        } else {
            $path = trim($path, '/');
        }
        // array_filter removes all empty values, without which the first variable may be an empty string instead of null
        @list($controller, $method, $params) = array_filter(explode('/', $path, 3));

        if (isset($controller)) {
            // make sure controller exists
            $obj = __NAMESPACE__ . '\controllers\\' . ucfirst(strtolower($controller)) . 'Controller';
            if (class_exists($obj)) {
                $this->controller = $obj;
            } else {
                // invalid controller specified
                @list($method, $params) = array_filter(explode('/', $path, 2));
            }
            unset($controller);
        }

        if (isset($method)) {
            // make sure a valid method exists, and is not inherited
            if (method_exists($this->controller, $method) && !method_exists(get_parent_class($this->controller), $method)) {
                $this->method = $method;
            } else {
                // invalid method specified
                $params = isset($params) ? "$method/$params" : $method;
            }
            unset($method);
        }

        // we should always have a valid controller and method at this point, even if the defaults
        // make sure we have the appropriate number of parameters, if arguments exist
        if (method_exists($this->controller, $this->method)) {
            $ref_class = new \ReflectionClass($this->controller);
            $ref_method = $ref_class->getMethod($this->method);
            $args = explode('/', $params);
            $total_args = count($args);
            if ($ref_method->getNumberOfParameters() > 0 &&
                $total_args >= $ref_method->getNumberOfRequiredParameters() &&
                $total_args <= $ref_method->getNumberOfParameters()) {
                $this->params = $args;
            } else {
                // invalid request
                self::notFound();
            }
        } else {
            // invalid request
            self::notFound();
        }
        unset($params);

        // get required dependencies and peform any last minute checks before routing
        $this->model = new ModelFactory(DB_HOST, DB_NAME, DB_USER, DB_PASS);
        $this->view = new View();
        $this->checkRepository();

        call_user_func_array([new $this->controller($this->model, $this->view, $this), $this->method], $this->params);
    }

    // redirect to specified page, or home page
    public function redirect($path = null)
    {
        if (isset($path)) {
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                header('Location: ' . $path);
            } elseif (filter_var(BASE_URL . $path, FILTER_VALIDATE_URL)) {
                header('Location: ' . BASE_URL . $path);
            } else {
                self::notFound();
            }
        } else {
            header('Location: ' . BASE_URL);
        }
        exit;
    }

    // generic error 404 page
    public function notFound()
    {
        header("HTTP/1.1 404 Not Found");
        echo '<html>' . "\r\n";
        echo '<head>' . "\r\n";
        echo '<title>Page Not Found</title>' . "\r\n";
        echo '</head>' . "\r\n";
        echo '<body>' . "\r\n";
        echo '<h1>404</h1>' . "\r\n";
        echo '</body>' . "\r\n";
        echo '</html>';
        exit;
    }
}
