<?php

namespace Core\Swoole\Route;

use Core\Base\CoSingle;

class Route
{
    /**
     * @var $requestManager RequestManager
     */
    private $requestManager;

    /**
     * @var $responseManager ResponseManager
     */
    private $responseManager;

    private $appName;

    private $moduleName;

    private $controllerName;

    private $actionName;

    private $application;

    public function __construct($request, $response)
    {
        $this->requestManager = $request;
        $this->responseManager = $response;

//        $this->moduleName = $this->requestManager->index('module');
        $this->appName = $this->requestManager->index('app');
        $this->controllerName = $this->requestManager->index('controller');
        $this->actionName = $this->requestManager->index('action');
        if (empty($this->appName) || empty($this->controllerName) || empty($this->actionName)) {
            throw new \Exception('application not found:' . $this->appName . '_'.$this->controllerName . '_' . $this->actionName, 403);
        }

        $serviceClass = 'app\\' . ucfirst($this->appName) . '\\Controller\\' . ucfirst($this->controllerName) . 'Controller';
        if (!class_exists($serviceClass)) {
            throw new \Exception('service not found:' . $serviceClass, 403);
        }

        $this->application = _class($serviceClass);
    }

    public function execute()
    {
        return call_user_func([$this->getApplication(), 'Action' . $this->getActionName()]);
    }

    public function getModuleName()
    {
        return $this->moduleName;
    }

    public function getControllerName()
    {
        return $this->controllerName;
    }

    public function getActionName()
    {
        return $this->actionName;
    }

    public function getApplication()
    {
        return $this->application;
    }
}