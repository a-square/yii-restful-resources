<?php

namespace Lightsoft\REST;

class PluralResourceUrlRule extends \CBaseUrlRule {
    public $path;
    private $_controller = null;
    public $idPattern = '%\d+%';
    public $idName = 'id';
    public $subresources = array();

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

    protected function collectionVerbMapping() {
        return array(
            'GET' => 'index',
            'POST' => 'create',
        );
    }

    protected function itemVerbMapping() {
        return array(
            'GET' => 'view',
            'PUT' => 'update',
            'DELETE' => 'destroy',
        );
    }

    protected function verbMapping() {
        return array(
            'GET' => 'view',
            'POST' => 'create',
            'PUT' => 'update',
            'DELETE' => 'destroy',
        );
    }

    /** {@inheritDoc} */
    public function createUrl($manager, $route, $params, $ampersand) {
        return false; // TODO: implement
    }

    /** {@inheritDoc} */
    public function parseUrl($manager, $request, $pathInfo, $rawPathInfo) {
        // we capture all requests with the given path
        if (strpos($pathInfo, $this->path) !== 0) {
            return false;
        }

        // capture id, if given
        $id = null;
        $leftovers = substr($pathInfo, strlen($this->path));
        if ($leftovers != '' && $leftovers != '/') {
            if (preg_match($this->idPattern, $leftovers, $matches, PREG_OFFSET_CAPTURE) === 1) {
                $match = $matches[0];
                $_REQUEST[$this->idName] = $_GET[$this->idName] = $match[0];
                $leftovers = substr($leftovers, $match[1] + strlen($match[0]));
            }
        }

        // if leftovers look like they could be a subresource route, try each subresource
        $exception = null;
        if ($leftovers[0] == '/') {
            foreach ($this->subresources as $subresource) {
                if (!($subresource instanceof \CBaseUrlRule)) {
                    $subresource = \Yii::createComponent($subresource);
                }

                // TODO: refactor
                try {
                    $route = $subresource->parseUrl($manager, $request, substr($leftovers, 1), $rawPathInfo);
                    if ($route !== false) {
                        return $route;
                    }
                } catch (\CHttpException $e) {
                    // we only handle 405 Not Allowed in a special way, because
                    // it could be that e.g. POST /api/foo/:id/bar is rejected
                    // by the bar subresource, but the foo resource's controller
                    // still has actionCreateBar($id) that also fits the URL
                    if ($e->statusCode != 405) {
                        throw $e;
                    }
                    
                    // store the 405 exception so that it could be rethrown if we can't make sense of the path ourselves
                    $exception = $e;
                }
            }
        }

        // capture and validate the method
        $method = '';
        if ($leftovers != '' && $leftovers != '/') {
            if (preg_match('%^/(\w+)/?%', $leftovers, $matches) !== 1) {
                // if we can't make sense of the path leftovers, and one of our
                // subresources had previously thrown 405 Not Allowed, assume
                // that since it was the best fit fot the URL, it should be the
                // one reporting the error, and thus rethrow that exception
                if ($exception) {
                    throw $exception;
                }
                
                // otherwise, assume that the the path probably has not ended
                // yet and defer to the resources later in the chain of
                // responsibility
                return false;
            }

            $method = $matches[1];
        }
        
        // remove the Allow header previously set by subresources
        // when throwing 405 Not Allowed, if any
        @header_remove('Allow');

        // method suffix is part of the action naming convention
        $methodSuffix = ucfirst($method);

        // make sure the controller exists
        $ca = \Yii::app()->createController($this->controller);
        if (!$ca) {
            throw new \CHttpException(500, 'Missing controller ' . $this->controller);
        }

        $controller = $ca[0];

        // pick the right verb mapping based on if ID or method is given
        if ($method == '') {
            if (isset($_GET[$this->idName])) {
                $verbMapping = $this->itemVerbMapping();
            } else {
                $verbMapping = $this->collectionVerbMapping();
            }
        } else {
            $verbMapping = $this->verbMapping();
        }

        // remove mappings that would map to a missing action
        foreach ($verbMapping as $v => $action) {
            if (!$controller->createAction($action . $methodSuffix)) {
                unset($verbMapping[$v]);
            }
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
