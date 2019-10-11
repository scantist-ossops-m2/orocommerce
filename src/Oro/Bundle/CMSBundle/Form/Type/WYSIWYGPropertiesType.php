<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType as DBALWYSIWYGPropertiesType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Provides properties for WYSIWYG editor.
 */
class WYSIWYGPropertiesType extends AbstractType
{
    public const TYPE_SUFFIX = DBALWYSIWYGPropertiesType::TYPE_SUFFIX;

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr']['data-grapesjs-properties'] = $form->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return HiddenType::class;
    }
}