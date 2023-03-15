<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CheckboxType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Represents a form type for {@see ProductKitItem}.
 */
class ProductKitItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'labels',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'oro.product.productkititem.labels.label',
                    'required' => true,
                    'value_class' => ProductKitItemLabel::class,
                    'entry_options' => ['constraints' => [new NotBlank(), new Length(['max' => 255])]]
                ]
            )
            ->add('sortOrder', IntegerType::class, [
                'label' => 'oro.product.productkititem.sort_order.label',
                'required' => false,
            ])
            ->add('optional', CheckboxType::class, [
                'label' => 'oro.product.productkititem.optional.label',
                'required' => false,
            ])
            ->add('minimumQuantity', QuantityType::class, [
                'label' => 'oro.product.productkititem.minimum_quantity.label',
                'required' => false,
                'useInputTypeNumberValueFormat' => true,
            ])
            ->add('maximumQuantity', QuantityType::class, [
                'label' => 'oro.product.productkititem.maximum_quantity.label',
                'required' => false,
                'useInputTypeNumberValueFormat' => true,
            ])
            ->add('productUnit', ProductUnitSelectType::class, [
                'label' => 'oro.product.productkititem.product_unit.label',
                'required' => true,
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (PreSetDataEvent $event) {
            $event->getForm()->add('kitItemProducts', ProductKitItemProductsType::class, [
                'label' => 'oro.product.productkititem.products.label',
                'required' => true,
                'by_reference' => false,
                'kit_item' => $event->getData(),
            ]);
        });
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $kitItem = $form->getData();
        $view->vars['selectedProductsSkus'] = array_map(
            static fn (Product $product) => $product->getSkuUppercase(),
            (array) $kitItem?->getProducts()->toArray()
        );

        $view->vars['fieldsMap'] = [];
        foreach ($view->children as $name => $childView) {
            if ($name === 'labels' && isset($childView->children['values']->children)) {
                $childViewDefault = reset($childView->children['values']->children);
                $view->vars['fieldsMap'][$name] = [
                    'key' => $name,
                    'name' => $childViewDefault->vars['full_name'],
                    'id' => $childViewDefault->vars['id'],
                ];
            } else {
                $view->vars['fieldsMap'][$name] = [
                    'key' => $name,
                    'name' => $childView->vars['full_name'],
                    'id' => $childView->vars['id'],
                ];
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductKitItem::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'oro_product_kit_item';
    }
}
