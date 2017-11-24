<?php

namespace Knp\JsonSchemaBundle\Schema;

class SchemaRegistry
{
    protected $registry = array();
    protected $groups = array();

    public function register($alias, $namespace)
    {
        if ($this->hasAlias($alias) && $namespace !== $this->getNamespace($alias)) {
            throw new \Exception(sprintf(
                'Alias "%s" is already used for namespace "%s".',
                $alias,
                $this->registry[$alias]
            ));
        }

        $this->registry[$alias] = $namespace;
        $this->registry['strict_' . $alias] = $namespace;
    }

    public function getGroups($alias)
    {
        return !empty($this->groups[$alias]) ? $this->groups[$alias] : [];
    }

    public function groups($alias, array $groups = [])
    {
        $this->groups[$alias] = $groups;
    }

    public function all()
    {
        return $this->registry;
    }

    public function getNamespace($alias)
    {
        if (!$this->hasAlias($alias)) {
            throw new \Exception(sprintf(
                'Alias "%s" is not registered.',
                $alias
            ));
        }

        return $this->registry[$alias];
    }

    public function getAlias($namespace)
    {
        if (!$this->hasNamespace($namespace)) {
            throw new \Exception(sprintf(
                'Namespace "%s" is not registered.',
                $namespace
            ));
        }

        $aliases = array_flip($this->registry);
        return $aliases[$namespace];
    }

    public function hasAlias($alias)
    {
        return array_key_exists($alias, $this->registry);
    }

    public function hasNamespace($namespace)
    {
        return array_key_exists($namespace, array_flip($this->registry));
    }

    public function getAliases()
    {
        return array_keys($this->registry);
    }
}
