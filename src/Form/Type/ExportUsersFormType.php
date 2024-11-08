<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportUsersFormType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'hosts' => ['all'],
        ]);

        $resolver->setAllowedTypes('hosts', 'array');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('hosts', ChoiceType::class, [
                'label' => false,
                'multiple' => true,
                'expanded' => true,
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
                'choices' => array_merge(['Todos' => 'all'], $options['hosts']),
                'choice_attr' => function ($choice, string $key) {
                    $attrs = ['disabled' => 'true'];
                    if ('Todos' === $key) {
                        return ['checked' => 'checked', 'disabled' => false, 'data-choice-all' => null];
                    }

                    return $attrs;
                },
            ]);
    }
}
