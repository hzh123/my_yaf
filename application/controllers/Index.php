<?php

/**
 * Created by PhpStorm.
 * User: zhenghua.hao
 * Date: 15/3/31
 * Time: 上午11:53
 */
class IndexController extends BaseController
{
    public function indexAction()
    {
        $this->jsonReturn('hello  world !');
    }
}
