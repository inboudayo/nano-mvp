<?php

namespace framework\controllers;

use framework\FormHandler,
    framework\ModelFactory,
    framework\Router,
    framework\View;

class IndexController
{
    private $model;
    private $view;

    function __construct(ModelFactory $model, View $view)
    {
        $this->model = $model;
        $this->view = $view;
    }

    // call the view for the home page
    public function index()
    {
        $this->view->data['page_title'] = 'Nano MVP';
        $this->view->data['description'] = 'Nano MVP framework.';
        $this->view->data['keywords'] = 'nano, framework, mvp, model view presenter, mvc, model view controller, php, open source';
        $this->view->load('home');
    }
}
