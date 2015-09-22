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
     * Bound functions
     * @var array 
     */
    private $funcs = [];

    /**
     * Construct porxy of mandator
     * @param object $mandator
     */
    private function __construct($mandator)
    {
        $this->mandator = $mandator;
        $this->mandatorClassName = get_class($mandator);
    }

    /**
     * Add aspect objects
     * @param array $aspects
     */
    private function _add_aspects_($aspects)
    {
        if (is_array($aspects)) {
            foreach ($aspects as $aspect) {
                if (is_object($aspect)) {
                    $className = get_class($aspect);
                    $this->aspects[$className] = $aspect;
                } elseif (is_string($aspect) && class_exists($aspect)) {
                    $this->aspects[$aspect] = new $aspect();
                }
            }
        } elseif (is_object($aspects)) {
            $className = get_class($aspects);
            $this->aspects[$className] = $aspects;
        } elseif (is_string($aspects) && class_exists($aspects)) {
            $this->aspects[$aspects] = new $aspects();
        }
    }

    /**
     * Remove aspect objects
     * @param array $aspects
     */
    private function _remove_aspects_($aspects)
    {
        if (is_array($aspects)) {
            foreach ($aspects as $aspect) {
                if (is_object($aspect)) {
                    $className = get_class($aspect);
                    unset($this->aspects[$className]);
                } elseif (is_string($aspect)) {
                    unset($this->aspects[$aspect]);
                }
            }
        } elseif (is_object($aspects)) {
            $className = get_class($aspects);
            unset($this->aspects[$className]);
        } elseif (is_string($aspects)) {
            unset($this->aspects[$aspects]);
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
    static private function __get_match_funcs__($funcs, $methodName)
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
     * @param string $position
     * @param array $parameter
     * @return void
     */
    static private function __exec_funcs__($funcs, $position, $parameter)
    {
        if (empty($funcs[$position])) {
            return;
        }
        foreach ($funcs[$position] as $func) {
            call_user_func($func, $parameter);
        }
    }

    /**
     * Execute matched methods of aspect objects 
     * @param array $aspects
     * @param string $methodName
     * @param array $parameter
     */
    static private function __exec_aspects__($aspects, $methodName, $parameter)
    {
        foreach ($aspects as $aspect) {
            if (method_exists($aspect, $methodName)) {
                $method = new \ReflectionMethod($aspect, $methodName);
                $method->invoke($aspect, $parameter);
            }
        }
    }

    /**
     * Call method of mandator, meanwhile, execute matched methods of aspect objects and bound functions
     * @param object $mandator
     * @param string $mandatorClassName
     * @param string $methodName
     * @param array $arguments
     * @param array $aspects
     * @param array $funcs
     * @return mixed
     */
    static private function __call__($mandator, $mandatorClassName, $methodName, $arguments, $aspects, $funcs)
    {
        if (!is_object($mandator) || !method_exists($mandator, $methodName)) {
            return null;
        }
        $reflectionMethod = new \ReflectionMethod($mandator, $methodName);
        if (!$reflectionMethod->isPublic()) {
            return null;
        }
        $args = [];
        $parameters = $reflectionMethod->getParameters();
        foreach ($parameters as $key => $parameter) {
            $args[$parameter->getName()] = isset($arguments[$key]) ? $arguments[$key] : ($parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null);
        }
        $params = ['class' => $mandatorClassName, 'method' => $methodName, 'args' => $args];
        $funcs = self::__get_match_funcs__($funcs, $methodName);
        self::__exec_aspects__($aspects, $methodName . 'Before', $params);
        self::__exec_funcs__($funcs, 'before', $params);
        try {
            $result = $reflectionMethod->invokeArgs($mandator, $arguments);
            $params['return'] = $result;
            self::__exec_aspects__($aspects, $methodName . 'After', $params);
            self::__exec_funcs__($funcs, 'after', $params);
        } catch (\Exception $ex) {
            $params['exception'] = $ex;
            self::__exec_aspects__($aspects, $methodName . 'Exception', $params);
            self::__exec_funcs__($funcs, 'exception', $params);
        }
        self::__exec_aspects__($aspects, $methodName . 'Always', $params);
        self::__exec_funcs__($funcs, 'always', $params);
        return isset($result) ? $result : null;
    }

    public function __call($name, $arguments)
    {
        return self::__call__($this->mandator, $this->mandatorClassName, $name, $arguments, $this->aspects, $this->funcs);
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
