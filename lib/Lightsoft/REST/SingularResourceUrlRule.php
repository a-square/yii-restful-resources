<?php

namespace Lightsoft\REST;

class SingularResourceUrlRule extends \CBaseUrlRule {
    public $path;
    private $_controller = null;
    
    protected $verbMapping = array(
        'GET' => 'view',
        'POST' => 'create',
        'PUT' => 'update',
        'DELETE' => 'destroy',
    ); 

    public function getController() {
        if ($this->_controller) {
            return $this->_controller;
        } else {
            return $this->path;
        }
    }

    public function setController($controller) {
        $this->_controller = $controller;
    }

    /** {@inheritDoc} */
    public function createUrl($manager, $route, $params, $ampersand) {
        return false; // TODO: implement
    }

    /** {@inheritDoc} */
    public function parseUrl($manager, $request, $pathInfo, $rawPathInfo) {
        // we capture all requests with the given path
        if (strncmp($pathInfo, $this->path, strlen($this->path)) !== 0) {
            return false;
        }

        // extract the method from what's left after extracting the path
        // from pathInfo
        $method = '';
        $leftovers = (string)substr($pathInfo, strlen($this->path));
        
        if ($leftovers !== '') {
            if (preg_match('%^/(\w+)/?%', $leftovers, $matches) !== 1) {
                // the path probably has not ended yet so we defer to other rules
                return false;
            }

            $method = $matches[1];
        }
        
        // method suffix is part of the action naming convention
        $methodSuffix = ucfirst($method);

        // make sure the controller exists
        $ca = \Yii::app()->createController($this->controller);
        if (!$ca) {
            throw new \CHttpException(500, 'Missing controller ' . $this->controller);
        }

        // remove mappings that would map to a missing action
        $controller = $ca[0];
        $verbMapping = $this->verbMapping;
        foreach ($verbMapping as $v => $action) {
            if (!$controller->createAction($action . $methodSuffix)) {
                unset($verbMapping[$v]);
            }
        }
        
        // if none of the verbs have matched, it means the method is not
        // supported
        if(empty($verbMapping)) {
            throw new \CHttpException(404, "Invalid method $method");
        }

        // if the verb is unmapped, reject the request
        $verb = $request->requestType;
        if (!array_key_exists($verb, $verbMapping)) {
            @header('Allow: ' . join(', ', array_keys($verbMapping)));
            throw new \CHttpException(405);
        }

        return $this->controller . '/' . $verbMapping[$verb] . $methodSuffix;
    }

}
