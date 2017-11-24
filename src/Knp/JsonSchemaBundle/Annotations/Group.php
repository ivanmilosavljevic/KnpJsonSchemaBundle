<?php

namespace Knp\JsonSchemaBundle\Annotations;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Group
{
    /** @var string */
    public $group;
}
