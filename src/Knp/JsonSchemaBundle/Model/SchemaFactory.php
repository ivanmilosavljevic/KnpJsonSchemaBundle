<?php

namespace Knp\JsonSchemaBundle\Model;

class SchemaFactory
{
    public function createSchema($title, array $properties = array(), $type = null, $schemaUrl = null, $id = null, $additionalProperties = false)
    {
        $schema = new Schema();

        $schema->setTitle($title);
        $schema->setType($type);
        $schema->setSchema($schemaUrl);
        $schema->setId($id);
        $schema->setAdditionalProperties($additionalProperties);

        foreach ($properties as $property) {
            $schema->addProperty($property);
        }

        return $schema;
    }
}
