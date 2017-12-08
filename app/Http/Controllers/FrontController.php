<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class FrontController extends BaseController
{

    public function render($path)
    {
        if(empty($path)){
           return view()->make('index');
        }
        $templatePath=str_replace('/','.',$path);
        if(!view()->exists($templatePath)){
            abort(404);
        }
        return view()->make($templatePath);
    }
}