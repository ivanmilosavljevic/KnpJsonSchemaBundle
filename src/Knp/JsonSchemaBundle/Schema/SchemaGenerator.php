<?php

namespace Knp\JsonSchemaBundle\Schema;

use Knp\JsonSchemaBundle\Reflection\ReflectionFactory;
use Knp\JsonSchemaBundle\Schema\SchemaRegistry;
use Knp\JsonSchemaBundle\Model\SchemaFactory;
use Knp\JsonSchemaBundle\Model\Schema;
use Knp\JsonSchemaBundle\Model\PropertyFactory;
use Knp\JsonSchemaBundle\Model\Property;
use Knp\JsonSchemaBundle\Property\PropertyHandlerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SchemaGenerator
{
    protected $jsonValidator;
    protected $reflectionFactory;
    protected $schemaRegistry;
    protected $schemaFactory;
    protected $propertyFactory;
    protected $propertyHandlers;
    protected $strictPropertyHandlers;
    protected $aliases = array();

    const STRICT = 'strict';
    const LOOSE = 'loose';

    public function __construct(
        \JsonSchema\Validator $jsonValidator,
        UrlGeneratorInterface $urlGenerator,
        ReflectionFactory $reflectionFactory,
        SchemaRegistry $schemaRegistry,
        SchemaFactory $schemaFactory,
        PropertyFactory $propertyFactory
    ) {
        $this->jsonValidator     = $jsonValidator;
        $this->urlGenerator      = $urlGenerator;
        $this->reflectionFactory = $reflectionFactory;
        $this->schemaRegistry    = $schemaRegistry;
        $this->schemaFactory     = $schemaFactory;
        $this->propertyFactory   = $propertyFactory;
        $this->propertyHandlers  = new \SplPriorityQueue;
        $this->strictPropertyHandlers  = new \SplPriorityQueue;
    }

    public function generate($alias, $strict = self::LOOSE)
    {
        if ($strict == self::STRICT)
        {
            $alias = 'strict_'. $alias;
        }

        $this->aliases[] = $alias;

        $className = $this->schemaRegistry->getNamespace($alias);
        $refl      = $this->reflectionFactory->create($className);
        $schema    = $this->schemaFactory->createSchema(ucfirst($alias));
        
        $schema->setId('http://example.org');
        $schema->setSchema(Schema::SCHEMA_V3);
        $schema->setType(Schema::TYPE_OBJECT);

        foreach ($refl->getProperties() as $property) {
            $property = $this->propertyFactory->createProperty($property->name);

            if ($strict == self::STRICT)
            {
                $this->applyStrictPropertyHandlers($className, $property);
            }
            else
            {
                $this->applyPropertyHandlers($className, $property);
            }

            if (!$property->isIgnored() && $property->hasType(Property::TYPE_OBJECT) && $property->getObject()) {
                // Make sure that we're not creating a reference to the parent schema of the property
                if (!in_array($property->getObject(), $this->aliases)) {
                    $property->setSchema(
                        $this->generate($property->getObject(), self::STRICT)
                    );
                } else {
                    $property->setIgnored(true);
                }
            }

            if (!$property->isIgnored()) {
                $schema->addProperty($property);
            }
        }

        $schema->setGroups($this->schemaRegistry->getGroups($alias));

//        if (false === $this->validateSchema($schema)) {
//            $message = "Generated schema is invalid. Please report on" .
//                "https://github.com/KnpLabs/KnpJsonSchemaBundle/issues/new.\n" .
//                "The following problem(s) were detected:\n";
//            foreach ($this->jsonValidator->getErrors() as $error) {
//                $message .= sprintf("[%s] %s\n", $error['property'], $error['message']);
//            }
//            $message .= sprintf("Json schema:\n%s", json_encode($schema, JSON_PRETTY_PRINT));
//            throw new \Exception($message);
//        }

        return $schema;
    }

    public function registerPropertyHandler(PropertyHandlerInterface $handler, $priority)
    {
        $this->propertyHandlers->insert($handler, $priority);
    }


    public function registerStrictPropertyHandler(PropertyHandlerInterface $handler, $priority)
    {
        $this->strictPropertyHandlers->insert($handler, $priority);
    }

    public function getPropertyHandlers()
    {
        return array_values(iterator_to_array(clone $this->propertyHandlers));
    }

    /**
     * Validate a schema against the meta-schema provided by http://json-schema.org/schema
     *
     * @param Schema $schema a json schema
     *
     * @return boolean
     */
    private function validateSchema(Schema $schema)
    {
        $this->jsonValidator->check(
            json_decode(json_encode($schema->jsonSerialize())),
            json_decode(file_get_contents($schema->getSchema()))
        );

        return $this->jsonValidator->isValid();
    }

    private function applyPropertyHandlers($className, Property $property)
    {
        $propertyHandlers = clone $this->propertyHandlers;

        while ($propertyHandlers->valid()) {
            $handler = $propertyHandlers->current();

            $handler->handle($className, $property);

            $propertyHandlers->next();
        }
    }

    private function applyStrictPropertyHandlers($className, Property $property)
    {
        $propertyHandlers = clone $this->strictPropertyHandlers;

        while ($propertyHandlers->valid()) {
            $handler = $propertyHandlers->current();

            $handler->handle($className, $property);

            $propertyHandlers->next();
        }
    }
}
