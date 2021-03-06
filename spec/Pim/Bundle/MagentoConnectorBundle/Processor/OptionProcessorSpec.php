<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Processor;

use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\MagentoConnectorBundle\Merger\MappingMerger;
use Pim\Bundle\MagentoConnectorBundle\Mapper\MappingCollection;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Guesser\NormalizerGuesser;
use Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\OptionNormalizer;
use Pim\Bundle\CatalogBundle\Entity\Attribute;
use Pim\Bundle\CatalogBundle\Entity\AttributeOption;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OptionProcessorSpec extends ObjectBehavior
{
    function let(
        ChannelManager $channelManager,
        LocaleManager $localeManager,
        MappingMerger $storeViewMappingMerger,
        MappingMerger $attributeMappingMerger,
        MappingCollection $attributeMapping,
        WebserviceGuesser $webserviceGuesser,
        NormalizerGuesser $normalizerGuesser,
        Webservice $webservice,
        OptionNormalizer $optionNormalizer,
        StepExecution $stepExecution
    ) {
        $this->beConstructedWith(
            $webserviceGuesser,
            $normalizerGuesser,
            $localeManager,
            $storeViewMappingMerger,
            $attributeMappingMerger
        );
        $this->setStepExecution($stepExecution);

        $attributeMappingMerger->getMapping()->willReturn($attributeMapping);
        $attributeMapping->getTarget('color')->willReturn('color');
        $webserviceGuesser->getWebservice(Argument::any())->willReturn($webservice);
        $normalizerGuesser->getOptionNormalizer(Argument::cetera())->willReturn($optionNormalizer);
    }

    function it_normalizes_given_grouped_options(
        AttributeOption $optionRed,
        AttributeOption $optionBlue,
        Attribute $attribute,
        $optionNormalizer,
        $webservice
    ) {
        $optionRed->getAttribute()->willReturn($attribute);
        $attribute->getCode()->willReturn('color');

        $optionRed->getCode()->willReturn('red');
        $optionBlue->getCode()->willReturn('blue');

        $webservice->getStoreViewsList()->shouldBeCalled();
        $webservice->getAttributeOptions('color')->willReturn(array('red'));

        $optionNormalizer->normalize($optionRed, Argument::cetera())->willReturn(array('foo'));
        $optionNormalizer->normalize($optionBlue, Argument::cetera())->willReturn(array('bar'));

        $this->process(array(
            $optionRed,
            $optionBlue
        ))->shouldReturn(array(array('foo'), array('bar')));
    }

    function it_raises_an_exception_if_it_can_not_get_option_list_from_webservice(
        AttributeOption $optionRed,
        Attribute $attribute,
        $optionNormalizer,
        $webservice
    ) {
        $optionRed->getAttribute()->willReturn($attribute);
        $attribute->getCode()->willReturn('color');

        $optionRed->getCode()->willReturn('red');

        $webservice->getStoreViewsList()->shouldBeCalled();
        $webservice->getAttributeOptions('color')->willThrow('Pim\Bundle\MagentoConnectorBundle\Webservice\SoapCallException');

        $optionNormalizer->normalize($optionRed, Argument::cetera())->willReturn(array('foo'));

        $this->shouldThrow('Akeneo\Bundle\BatchBundle\Item\InvalidItemException')->during('process', array(array($optionRed)));
    }

    function it_raises_an_exception_if_a_error_occure_during_normalization_process(
        AttributeOption $optionRed,
        Attribute $attribute,
        $optionNormalizer,
        $webservice
    ) {
        $optionRed->getAttribute()->willReturn($attribute);
        $attribute->getCode()->willReturn('color');

        $optionRed->getCode()->willReturn('red');

        $webservice->getStoreViewsList()->shouldBeCalled();
        $webservice->getAttributeOptions('color')->willReturn(array('red'));

        $optionNormalizer->normalize($optionRed, Argument::cetera())->willThrow('Pim\Bundle\MagentoConnectorBundle\Normalizer\Exception\NormalizeException');

        $this->shouldThrow('Akeneo\Bundle\BatchBundle\Item\InvalidItemException')->during('process', array(array($optionRed)));
    }

    function it_gives_a_proper_configuration_for_fields($storeViewMappingMerger, $attributeMappingMerger)
    {
        $storeViewMappingMerger->getConfigurationField()->willReturn(array('fooo' => 'baar'));
        $attributeMappingMerger->getConfigurationField()->willReturn(array('foo' => 'bar'));

        $this->getConfigurationFields()->shouldReturn(array(
            'soapUsername' => array(
                'options' => array(
                    'required' => true,
                    'help'     => 'pim_magento_connector.export.soapUsername.help',
                    'label'    => 'pim_magento_connector.export.soapUsername.label'
                )
            ),
            'soapApiKey'   => array(
                //Should be remplaced by a password formType but who doesn't
                //empty the field at each edit
                'type'    => 'text',
                'options' => array(
                    'required' => true,
                    'help'     => 'pim_magento_connector.export.soapApiKey.help',
                    'label'    => 'pim_magento_connector.export.soapApiKey.label'
                )
            ),
            'soapUrl' => array(
                'options' => array(
                    'required' => true,
                    'help'     => 'pim_magento_connector.export.soapUrl.help',
                    'label'    => 'pim_magento_connector.export.soapUrl.label'
                )
            ),
            'defaultLocale' => array(
                'type' => 'choice',
                'options' => array(
                    'choices' => null,
                    'required' => true,
                    'attr' => array('class' => 'select2'),
                    'help'     => 'pim_magento_connector.export.defaultLocale.help',
                    'label'    => 'pim_magento_connector.export.defaultLocale.label'
                )
            ),
            'website' => array(
                'type' => 'text',
                'options' => array(
                    'required' => true,
                    'help'     => 'pim_magento_connector.export.website.help',
                    'label'    => 'pim_magento_connector.export.website.label'
                )
            ),
            'fooo' => 'baar',
            'foo' => 'bar',
        ));
    }
}
