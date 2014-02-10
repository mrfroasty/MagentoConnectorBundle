<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Mapper;

use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Pim\Bundle\MagentoConnectorBundle\Manager\SimpleMappingManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\AttributeManager;
use Pim\Bundle\MagentoConnectorBundle\Entity\SimpleMapping;
use Pim\Bundle\MagentoConnectorBundle\Validator\Constraints\MagentoUrlValidator;
use Pim\Bundle\CatalogBundle\Entity\Attribute;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ORMAttributeMapperSpec extends ObjectBehavior
{
    protected $clientParameters;

    function let(
        MagentoUrlValidator $magentoUrlValidator,
        SimpleMappingManager $simpleMappingManager,
        AttributeManager $attributeManager
    ) {
        $this->beConstructedWith($magentoUrlValidator, $simpleMappingManager, $attributeManager);
        $this->clientParameters = new MagentoSoapClientParameters('soap_user', 'soap_password', 'soap_url');
    }

    function it_gets_mapping_from_database($simpleMappingManager, $magentoUrlValidator, SimpleMapping $simpleMapping)
    {
        $this->setParameters($this->clientParameters);
        $magentoUrlValidator->isValidMagentoUrl(Argument::any())->willReturn(true);

        $simpleMapping->getSource()->willReturn('attribute_source');
        $simpleMapping->getTarget()->willReturn('attribute_target');
        $simpleMappingManager->getMapping($this->getIdentifier())->willReturn(array($simpleMapping));

        $mapping = $this->getMapping();

        $mapping->shouldBeAnInstanceOf('Pim\Bundle\MagentoConnectorBundle\Mapper\MappingCollection');
        $mapping->toArray()->shouldReturn(array(
            'attribute_source' => array(
                'source'    => 'attribute_source',
                'target'    => 'attribute_target',
                'deletable' => true
            )
        ));
    }

    function it_returns_an_empty_array_if_parameters_are_not_setted($simpleMappingManager, $magentoUrlValidator, SimpleMapping $simpleMapping)
    {
        $simpleMapping->getSource()->willReturn('attribute_source');
        $simpleMapping->getTarget()->willReturn('attribute_target');

        $mapping = $this->getMapping();

        $mapping->shouldBeAnInstanceOf('Pim\Bundle\MagentoConnectorBundle\Mapper\MappingCollection');
        $mapping->toArray()->shouldReturn(array());
    }

    function it_shoulds_store_mapping_in_database($simpleMappingManager, $magentoUrlValidator)
    {
        $this->setParameters($this->clientParameters);
        $magentoUrlValidator->isValidMagentoUrl(Argument::any())->willReturn(true);

        $simpleMappingManager->setMapping(array('mapping'), $this->getIdentifier())->shouldBeCalled();

        $this->setMapping(array('mapping'));
    }

    function it_shoulds_store_nothing_if_parameters_are_not_setted($simpleMappingManager)
    {
        $simpleMappingManager->setMapping(Argument::cetera())->shouldNotBeCalled();

        $this->setMapping(array('mapping'));
    }

    function it_shoulds_return_any_targets()
    {
        $this->getAllTargets()->shouldReturn(array());
    }

    function it_shoulds_return_all_attributes_from_database_as_sources($attributeManager, Attribute $attribute)
    {
        $attributeManager->getAttributes()->willReturn(array($attribute));

        $attribute->getCode()->willReturn('foo');

        $this->getAllSources()->shouldReturn(array('foo'));
    }

    function it_shoulds_have_a_priority()
    {
        $this->getPriority()->shouldReturn(10);
    }
}