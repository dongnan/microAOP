<?php

/**
 * microAOP - A mirco & powerful AOP library for PHP 
 *
 * @author      Dong Nan <hidongnan@gmail.com>
 * @copyright   (c) Dong Nan http://idongnan.cn All rights reserved.
 * @link        https://github.com/dongnan/microAOP/
 * @license     MIT ( http://mit-license.org/ )
 */

namespace microAOP;

/**
 * AOP Proxy
 *
 * @author DongNan <hidongnan@gmail.com>
 * @date 2015-9-18
 */
class Proxy
{

    /**
     * Mandator
     * @var object 
     */
    private $mandator;

    /**
     * Classname of mandator
     * @var string 
     */
    private $mandatorClassName;

    /**
     * Bound Aspect objects
     * @var array 
     */
    private $aspects = [];

    /**
     *
     * @var array 
     */
    private $aspectMethods = [];

    /**
     * Bound functions
     * @var array 
     */
    private $funcs = [];

    /**
     * 
     * @var array 
     */
    static private $_params = [];

    /**
     * Construct porxy of mandator
     * @param object $mandator
     */
    private function __construct($mandator)
    {
        $this->mandator = $mandator;
        $this->mandatorClassName = get_class($mandator);
        if (!isset(self::$_params[$this->mandatorClassName])) {
            self::$_params[$this->mandatorClassName] = [];
        }
    }

    private function _add_aspect_($name, $object)
    {
        if (!in_array($name, $this->aspects)) {
            $this->aspects[] = $name;
            $methods = get_class_methods($object);
            foreach ($methods as $method) {
                if (!isset($this->aspectMethods[$method])) {
                    $this->aspectMethods[$method] = [];
                }
                $this->aspectMethods[$method][$name] = $object;
            }
        }
    }

    /**
     * Add aspect objects
     * @param array $aspects
     */
    private function _add_aspects_($aspects)
    {
        foreach ($aspects as $aspect) {
            if (is_object($aspect)) {
                $className = get_class($aspect);
                $this->_add_aspect_($className, $aspect);
            } elseif (is_string($aspect) && class_exists($aspect)) {
                $this->_add_aspect_($aspect, new $aspect());
            }
        }
    }

    private function _remove_aspect_($name)
    {
        if ($key = array_search($name, $this->aspects) !== false) {
            unset($this->aspects[$key]);
            foreach ($this->aspectMethods as $method => &$objects) {
                unset($objects[$name]);
                if (empty($objects)) {
                    unset($this->aspectMethods[$method]);
                }
            }
        }
    }

    /**
     * Remove aspect objects
     * @param array $aspects
     */
    private function _remove_aspects_($aspects)
    {
        foreach ($aspects as $aspect) {
            if (is_object($aspect)) {
                $className = get_class($aspect);
                $this->_remove_aspect_($className);
            } elseif (is_string($aspect)) {
                $this->_remove_aspect_($aspect);
            }
        }
    }

    /**
     * Add functions
     * @param string $rules
     * @param string $position
     * @param array $funcs
     */
    private function _add_funcs_($rules, $position, $funcs)
    {
        if (!isset($this->funcs[$rules])) {
            $this->funcs[$rules] = [];
        }
        if (!isset($this->funcs[$rules][$position])) {
            $this->funcs[$rules][$position] = [];
        }
        foreach ($funcs as $func) {
            if (is_callable($func)) {
                $this->funcs[$rules][$position][] = $func;
            }
        }
    }

    /**
     * Remove functions
     * @param string $rules
     * @param string $position
     */
    private function _remove_funcs_($rules, $position = null)
    {
        if (empty($position)) {
            if (isset($this->funcs[$rules])) {
                unset($this->funcs[$rules]);
            }
        } else {
            if (isset($this->funcs[$rules][$position])) {
                unset($this->funcs[$rules][$position]);
            }
        }
    }

    /**
     * Bind aspects to proxy of mandator
     * @param self $mandator    mandator
     * @param mixed $_          aspect to be bind
     * @return boolean
     */
    static public function __bind__(&$mandator, $_ = null)
    {
        if (is_object($mandator)) {
            $args = func_get_args();
            array_shift($args);
            if (!($mandator instanceof self)) {
                $mandator = new self($mandator);
            }
            $mandator->_add_aspects_($args);
            return true;
        }
        return false;
    }

    /**
     * Unbind aspects in proxy of mandator
     * @param self $mandator    mandator
     * @param mixed $_          aspect to be unbind
     * @return boolean
     */
    static public function __unbind__(&$mandator, $_ = null)
    {
        if ($mandator instanceof self) {
            $args = func_get_args();
            array_shift($args);
            $mandator->_remove_aspects_($args);
            return true;
        }
        return false;
    }

    /**
     * Bind functions to proxy of mandator
     * @param object $mandator  mandator
     * @param string $rules     the rules to match method of mandator
     * @param string $position  position is before,after,exception or always
     * @param mixed $_          function to be bind
     * @return boolean
     */
    static public function __bind_func__(&$mandator, $rules, $position, $_ = null)
    {
        if (is_object($mandator) && !empty($rules)) {
            $args = func_get_args();
            array_splice($args, 0, 2);
            if (!($mandator instanceof self)) {
                $mandator = new self($mandator);
            }
            $mandator->_add_funcs_($rules, $position, $args);
            return true;
        }
        return false;
    }

    /**
     * Unbind functions in proxy of mandator
     * @param self $mandator    mandator
     * @param string $rules     the rules to match method of mandator
     * @param string $position  position is before,after,exception or always
     * @return boolean
     */
    static public function __unbind_func__(&$mandator, $rules, $position = null)
    {
        if ($mandator instanceof self && !empty($rules)) {
            $args = func_get_args();
            array_splice($args, 0, 2);
            $mandator->_remove_funcs_($rules, $position);
            return true;
        }
        return false;
    }

    /**
     * Get mathced functions of bound functions
     * @param array $funcs          all bound functions
     * @param string $methodName    the called method name of mandator
     * @return array
     */
    static private function _get_match_funcs_($funcs, $methodName)
    {
        if (empty($funcs)) {
            return [];
        }
        $result = [];
        foreach ($funcs as $rules => $value) {
            if (preg_match("/^[A-Za-z]/", $rules[0])) {
                $rules = "/^{$rules}$/";
            }
            if (preg_match($rules, $methodName)) {
                foreach ($value as $position => $fns) {
                    if (!isset($result[$position])) {
                        $result[$position] = [];
                    }
                    $result[$position] = array_merge($result[$position], $fns);
                }
            }
        }
        return $result;
    }

    /**
     * Execute matched and bound functions
     * @param array $funcs
     * @param array $parameter
     * @return void
     */
    static private function _exec_funcs_($funcs, $parameter)
    {
        foreach ($funcs as $func) {
            call_user_func($func, $parameter);
        }
    }

    /**
     * Execute matched methods of aspect objects 
     * @param array $aspects
     * @param string $methodName
     * @param array $parameter
     */
    static private function _exec_aspects_($aspects, $methodName, $parameter)
    {
        foreach ($aspects as $aspect) {
            $aspect->$methodName($parameter);
        }
    }

    public function __call($name, $arguments)
    {
        if (!is_object($this->mandator) || !method_exists($this->mandator, $name)) {
            return null;
        }
        $reflectionMethod = new \ReflectionMethod($this->mandator, $name);
        if (!$reflectionMethod->isPublic()) {
            return null;
        }
        $args = [];
        if (isset(self::$_params[$this->mandatorClassName][$name])) {
            foreach (self::$_params[$this->mandatorClassName][$name] as $key => $param) {
                $args[$param['name']] = isset($arguments[$key]) ? $arguments[$key] : $param['default'];
            }
        } else {
            self::$_params[$this->mandatorClassName][$name] = [];
            $parameters = $reflectionMethod->getParameters();
            foreach ($parameters as $key => $parameter) {
                $param = ['name' => $parameter->getName(), 'default' => $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null];
                self::$_params[$this->mandatorClassName][$name][$key] = $param;
                $args[$param['name']] = isset($arguments[$key]) ? $arguments[$key] : $param['default'];
            }
        }

        $params = ['class' => $this->mandatorClassName, 'method' => $name, 'args' => $args];
        $funcs = self::_get_match_funcs_($this->funcs, $name);
        empty($this->aspectMethods["{$name}Before"]) || self::_exec_aspects_($this->aspectMethods["{$name}Before"], "{$name}Before", $params);
        empty($funcs['before']) || self::_exec_funcs_($funcs['before'], $params);
        try {
            $result = $reflectionMethod->invokeArgs($this->mandator, $arguments);
            $params['return'] = $result;
            empty($this->aspectMethods["{$name}After"]) || self::_exec_aspects_($this->aspectMethods["{$name}After"], "{$name}After", $params);
            empty($funcs['after']) || self::_exec_funcs_($funcs['after'], $params);
        } catch (\Exception $ex) {
            $params['exception'] = $ex;
            empty($this->aspectMethods["{$name}Exception"]) || self::_exec_aspects_($this->aspectMethods["{$name}Exception"], "{$name}Exception", $params);
            empty($funcs['exception']) || self::_exec_funcs_($funcs['exception'], $params);
        }
        empty($this->aspectMethods["{$name}Always"]) || self::_exec_aspects_($this->aspectMethods["{$name}Always"], "{$name}Always", $params);
        empty($funcs['always']) || self::_exec_funcs_($funcs['always'], $params);
        return isset($result) ? $result : null;
    }

    public function __set($name, $value)
    {
        if (!is_object($this->mandator)) {
            return false;
        }
        $this->mandator->$name = $value;
    }

    public function __get($name)
    {
        if (!is_object($this->mandator)) {
            return false;
        }
        return $this->mandator->$name;
    }

}
