<?php

namespace Core;

use Core\Base\CoSingle;

class BaseObject
{

    public $initBehaviorFlag = false;

    public function __construct()
    {
        $this->initBehavior();
    }

    public function on($name, $handle)
    {
        Event::on(get_class($this), $name, $handle[0], isset($handle[1]) ? $handle[1] : []);
    }

    public function off($name, $handle)
    {
        Event::off(get_class($this), $name, $handle[0]);
    }

    public function trigger($name, $event = null)
    {
        $this->initBehavior();
        Event::trigger(get_class($this), $name, $event);
    }

    public function behavior()
    {
        return [];
    }

    public function initBehavior()
    {
        if ($this->initBehaviorFlag === false) {
            foreach ($this->behavior() as $action => $behavior) {
                if (is_array($behavior)) {
                    /** @var $events Behavior */
                    $className = $behavior['class'] . 'Behavior';
                    $events = new $className;
                    foreach ($events->events() as $name => $handle) {
                        $this->on($name, $handle);
                    }
                }
            }
            $this->initBehaviorFlag = true;
        }
    }
}