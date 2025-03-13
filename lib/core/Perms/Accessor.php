<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Interface providing convenient access to permissions in
 * a resolver for a set of groups. The permissions can be
 * accessed on the resolver as properties.
 *
 * The globalize() method also allows to deploy the permissions
 * in their global variables.
 */
class Perms_Accessor implements ArrayAccess
{
    private $resolver;
    private $prefix = '';
    private $permissions = [];
    private $context = [];
    private $groups = [];
    private $checkSequence = null;

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function setGroups(array $groups)
    {
        $this->groups = $groups;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function setResolver(Perms_Resolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function getResolver()
    {
        return $this->resolver;
    }

    public function from()
    {
        return $this->resolver->from();
    }

    public function setPermission($key, $value)
    {
        $this->permissions[$key] = $value;
    }

    public function setContext(array $context)
    {
        $this->context = $context;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function setCheckSequence(array $sequence)
    {
        $this->checkSequence = $sequence;
    }

    public function __get($name)
    {

        if ($this->resolver) {
            $name = $this->sanitize($name);

            return $this->checkPermission($name);
        } else {
            return false;
        }
    }

    private function checkPermission($name)
    {
        if ($this->checkSequence) {
            foreach ($this->checkSequence as $check) {
                if ($check->check($this->resolver, $this->context, $name, $this->groups)) {
                    return true;
                }
            }

            return false;
        } else {
            return $this->resolver->check($name, $this->groups);
        }
    }

    public function globalize($permissions, $smarty = null, $sanitize = true)
    {
        foreach ($permissions as $perm) {
            if ($sanitize) {
                $perm = $this->sanitize($perm);
            }
            $val = $this->checkPermission($perm) ? 'y' : 'n';
            $GLOBALS[ $this->prefix . $perm ] = $val;

            if ($smarty) {
                $smarty->assign('tiki_p_' . $perm, $val);
            }
        }
    }

    private function sanitize($name)
    {
        if ($this->prefix && $name[0] == $this->prefix[0] && strpos($name, $this->prefix) === 0) {
            return substr($name, strlen($this->prefix));
        } else {
            return $name;
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($name)
    {
        return $this->__get($name);
    }

    public function offsetSet($name, $value): void
    {
    }

    public function offsetUnset($name): void
    {
    }

    public function offsetExists($name): bool
    {
        return true;
    }

    public function applicableGroups()
    {
        if ($this->checkSequence) {
            $groups = [];
            foreach ($this->checkSequence as $check) {
                $groups = array_merge($groups, $check->applicableGroups($this->resolver));
            }

            return array_unique($groups);
        } else {
            return $this->resolver->applicableGroups();
        }
    }
}
