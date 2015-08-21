<?php
class YafView implements  Yaf_View_Interface
{
    protected $_templatePath = '';
    protected $_templatesDirectory;
    protected $_templateLayout;
    protected $_request;
    protected $_data = array();


    public function __construct($tempalte_dir)
    {
        $this->_templatesDirectory = $tempalte_dir;
    }

    public function setRequest($request)
    {
        $this->_request = $request;
    }

    /**
     * @return Yaf_Request_Http
     */
    public function getRequest()
    {
        return $this->_request;
    }

    public function disableLayout()
    {
        $this->_templateLayout = null;
    }

    /**
     * @param  $layout script path of the layout
     * @return YafView
     */
    public function enableLayout($layout)
    {
        $this->_templateLayout = $layout;

        return $this;
    }

    /**
     * Append new data to existing template data
     * @param  array
     * @throws InvalidArgumentException If not given an array argument
     */
    public function appendData($data)
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Cannot append view data. Expected array argument.');
        }
        $this->_data = array_merge($this->_data, $data);
    }

    /**
     * Get templates directory
     * @return string|null     Path to templates directory without trailing slash;
     *                         Returns null if templates directory not set;
     */
    public function getScriptPath()
    {
        return $this->_templatesDirectory;
    }

    /**
     * Set templates directory
     * @param  string   $dir
     */
    public function setScriptPath($dir)
    {
        $this->_templatesDirectory = rtrim($dir, '/');
    }

    /**
     * Set template
     * @param  string           $template
     * @throws RuntimeException If template file does not exist
     *
     * DEPRECATION WARNING!
     * This method will be removed in the near future.
     */
    public function setTemplate($template)
    {
        $this->_templatePath = $this->getScriptPath() . '/' . ltrim($template, '/');
        if (!file_exists($this->_templatePath)) {
            throw new \RuntimeException('View cannot render template `' . $this->_templatePath . '`. Template does not exist.');
        }
    }

    /**
     * @param string $tpl
     * @param array  $tpl_vars
     * @return bool
     */
    public function display($tpl, $tpl_vars = null)
    {
        echo $this->fetch($tpl, $tpl_vars);
    }

    /**
     * Fetch rendered template
     *
     * This method returns the rendered template
     *
     * @param  string $template Pathname of template file relative to templates directory
     * @return string
     */
    public function fetch($template, $tpl_vars = NULL)
    {
        return $this->render($template, $tpl_vars);
    }



    /**
     * Render template
     *
     * @param  string   $template   Pathname of template file relative to templates directory
     * @return string
     *
     * DEPRECATION WARNING!
     * Use `\Slim\View::fetch` to return a rendered template instead of `\Slim\View::render`.
     */
    public function render($template, $tpl_vars=null)
    {
        if ($tpl_vars !== null)
        {
            $this->clearVars();
            $this->assign($tpl_vars);
        }

        if (!empty($this->_templateLayout))
        {
            $oldVars = $this->getVars();
//            $this->clearVars();
            $this->template_content = $template;
            $this->template_content_vars = $oldVars;
//            $this->content = $this->partial($template, $oldVars);
//            $this->controller = strtolower($this->getRequest()->getControllerName());
//            $this->action = strtolower($this->getRequest()->getActionName());
            $template = $this->_templateLayout;
        }

        $this->setTemplate($template);
//        ob_start();
        include $this->_templatePath;
//        return ob_get_clean();
        return '';
    }

    public function partial($template, $data=array(), $retStr=true, $setDir = null)
    {
        $oldVars = $this->getVars();
        $this->clearVars()->assign($data);

//        $partPath = $this->getScriptPath() . '/' . ltrim($template, '/');
        if ($setDir != null)
        {
            $partPath = $setDir . '/' . ltrim($template, '/');
        } else
        {
            $partPath = $this->getScriptPath() . '/' . ltrim($template, '/');
        }

        if (!file_exists($partPath)) {
            throw new \RuntimeException('View cannot render template `' . $partPath . '`. Template does not exist.');
        }

        if ($retStr)
        {
            ob_start();
            include $partPath;
            $partView = ob_get_clean();
        }
        else
        {
            include $partPath;
            $partView = '';
        }

        $this->clearVars()->assign($oldVars);

        return $partView;
    }

    // from zend view
    public function assign($spec, $value = null)
    {
        // which strategy to use?
        if (is_string($spec))
        {
            // assign by name and value
            if ('_' == substr($spec, 0, 1))
            {
                throw new \InvalidArgumentException('Setting private or protected class members is not allowed');
            }
            $this->$spec = $value;
        }
        elseif (is_array($spec))
        {
            // assign from associative array
            $error = false;
            foreach ($spec as $key => $val)
            {
                if ('_' == substr($key, 0, 1))
                {
                    $error = true;
                    break;
                }
                $this->$key = $val;
            }
            if ($error)
            {
                throw new \InvalidArgumentException('Setting private or protected class members is not allowed');
            }
        }
        else
        {
//            throw new \InvalidArgumentException('assign() expects a string or array, received ' . gettype($spec));
        }

        return $this;
    }

    /**
     * Return list of all assigned variables
     *
     * Returns all public properties of the object. Reflection is not used
     * here as testing reflection properties for visibility is buggy.
     *
     * @return array
     */
    public function getVars()
    {
        $vars   = get_object_vars($this);
        foreach ($vars as $key => $value)
        {
            if ('_' == substr($key, 0, 1))
            {
                unset($vars[$key]);
            }
        }

        return $vars;
    }

    /**
     * Clear all assigned variables
     *
     * Clears all variables assigned to Zend_View either via {@link assign()} or
     * property overloading ({@link __set()}).
     *
     * @return HView
     */
    public function clearVars()
    {
        $vars   = get_object_vars($this);
        foreach ($vars as $key => $value)
        {
            if ('_' != substr($key, 0, 1))
            {
                unset($this->$key);
            }
        }

        return $this;
    }
}
