<?php

class IceModelBehavior extends Behavior
{
	public function queryMethods($builder)
	{
		$this->builder = $builder;
    $script = '';

    $this->addQueryCallStatic($script);

		return $script;
	}
	
	public function addQueryCallStatic(&$script)
	{
		$script .= "
/**
 * @return {$this->builder->getStubQueryBuilder()->getClassname()} The current query, for fluid interface
 */
public function __call(\$name, \$arguments)
{
  if (method_exists('iceModel{$this->builder->getStubQueryBuilder()->getClassname()}', \$name))
  {
    \$q = new iceModel{$this->builder->getStubQueryBuilder()->getClassname()}();
    \$arguments[] = \$this;

    return call_user_func_array(array(\$q, \$name), \$arguments);
  }

  return parent::__call(\$name, \$arguments);
}
";
	}
}
