<?php

namespace Knp\JsonSchemaBundle\Property;

use Knp\JsonSchemaBundle\Model\Property;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Types\Type;

class DoctrineReflectionConstraintsHandler implements PropertyHandlerInterface
{
    private $em;

    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
    }

    public function handle($className, Property $property)
    {
        $type = $this->getConstraintsForProperty($className, $property);
        
        if (in_array($type, array(Type::BIGINT, Type::SMALLINT, Type::INTEGER))) {
            $property->addType(Property::TYPE_INTEGER);
        }
        if (in_array($type, array(Type::BIGINT, Type::SMALLINT, Type::INTEGER, Type::DECIMAL, Type::FLOAT))) {
            $property->addType(Property::TYPE_NUMBER);
        }
        if (in_array($type, array(Type::STRING, Type::TEXT))) {
            $property->addType(Property::TYPE_STRING);
        }
        if (in_array($type, array(Type::BOOLEAN))) {
            $property->addType(Property::TYPE_BOOLEAN);
        }
        if (in_array($type, array(Type::TARRAY))) {
            $property->addType(Property::TYPE_ARRAY);
        }
        if (in_array($type, array(Type::OBJECT))) {
            $property->setObject($this->em->getMetadataFactory()->getMetadataFor($className)->getAssociationTargetClass($property->getName()));
            $property->addType(Property::TYPE_OBJECT);
        }
        if (in_array($type, array(Type::DATE))) {
            $property->setFormat(Property::FORMAT_DATE);
        }
        if (in_array($type, array(Type::DATETIME, Type::DATETIMETZ))) {
            $property->setFormat(Property::FORMAT_DATETIME);
        }
        if (in_array($type, array(Type::TIME))) {
            $property->setFormat(Property::FORMAT_TIME);
        }
    }

    private function getConstraintsForProperty($className, Property $property)
    {
        $classMetadata = $this->em->getMetadataFactory()->getMetadataFor($className);
        foreach ($classMetadata->getFieldNames() as $fieldName) {
            if ($fieldName === $property->getName()) {
                return $classMetadata->getTypeOfField($fieldName);
            }
        }

        foreach ($classMetadata->getAssociationNames() as $associationName) {
            if ($associationName === $property->getName()) {
                return ($classMetadata->isSingleValuedAssociation($associationName) ? Type::OBJECT : 
                       ($classMetadata->isCollectionValuedAssociation($associationName) ? Type::TARRAY : Property::TYPE_NULL));
            }
        }

        return Property::TYPE_NULL;
    }
}
