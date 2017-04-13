<?php

namespace framework;

use framework\FormHandler;

class View
{
    public $data = []; // for passing "non-persistent" data

    public function load($view, FormHandler $form = null)
    {
        // sanitize data
        if (!empty($this->data)) {
            //extract(array_map(array('self', 'sanitize'), $this->data));
            $this->data = array_map(['self', 'sanitize'], $this->data);
        }
        if (is_object($form)) {
            if (!empty($form->data)) {
                $form->data = array_map(['self', 'sanitize'], $form->data);
            }
        } else {
            unset($form);
        }

        // if not defined, set title based on view
        if (!isset($this->data['page_title'])) {
            $this->data['page_title'] = ucwords(str_replace(['_', '/'], ' ', $view));
        }
        $this->data['page_title'] .= ' | ' . SITE_TITLE;

        // output the page
        require('views/header.php');
        require("views/$view.php");
        require('views/footer.php');
        unset($view);

        // dump all vars for development
        if (DEVELOPMENT) {
            echo '<pre>';
            echo '<h3>explicit variables:</h3>';
            print_r(get_defined_vars());
            echo '<h3>view data:</h3>';
            var_dump($this->data);
            echo '<h3>session:</h3>';
            var_dump($_SESSION);
            echo '</pre>';
        }

        // end script execution time (starts in bootstrap)
        echo '<!-- ' . (microtime(TRUE) - EXE_TIME_START) . ' -->';
        exit;
    }

    // sanitize output
    public function sanitize($output)
    {
        if (is_array($output)) {
            return array_map(['self', 'sanitize'], $output);
        }
        if (get_magic_quotes_gpc()) {
            $output = stripslashes($output);
        }

        return htmlentities($output, ENT_QUOTES, 'UTF-8');
    }

    // draw a menu for navigating multiple pages of dynamic content
    public function paginate($page, $display, $total)
    {
        // semantic
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = str_replace(BASE_PATH, '', $path);
        if ($path != '/') {
            $path = rtrim($path, '/');
            if (stristr($path, 'page/')) {
                $path = rtrim($path, '0..9');
                $query = '';
            } else {
                $query = '/page/';
            }
        } else {
            $query = 'page/';
        }

        /* // non-semantic alternative
        $path = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'utf-8');
        if(isset($_SERVER['QUERY_STRING']) && trim($_SERVER['QUERY_STRING']) != '') {
            if(stristr($_SERVER['QUERY_STRING'], 'page=')) {
                $query = '?' . rtrim($_SERVER['QUERY_STRING'], '0..9');
            } else {
                $query = '?' . $_SERVER['QUERY_STRING'] . '&page=';
            }
        } else {
            $query = '?page=';
        } */

        // create navigation links
        $pages = $total <= $display ? 1 : ceil($total / $display);
        $first = '<a href="' . $path . $query. '1">first</a>';
        $prev = '<a href="' . $path . $query . ($page - 1) . '">prev</a>';
        $next = '<a href="' . $path . $query . ($page + 1) . '">next</a>';
        $last = '<a href="' . $path . $query . $pages . '">last</a>';

        echo ($page > 1) ? "$first &#171; $prev &#171;" : 'first &#171; prev &#171;';
        echo ' (page ' . $page . ' of ' . $pages . ') ';
        echo ($page < $pages) ? "&#187; $next &#187; $last" : '&#187; next &#187; last';
    }
}
