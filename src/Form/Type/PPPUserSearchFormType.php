<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PPPUserSearchFormType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'hosts' => [],
            'allow_extra_fields' => true,
        ]);

        $resolver->setAllowedTypes('hosts', 'array');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $hosts = array_merge(['Todos' => 'all'], $options['hosts']);
        $builder
            ->add('query', SearchType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Digite um nome, IP ou MAC de usuário',
                ],
            ])
            ->add('hosts', ChoiceType::class, [
                'label' => false,
                'multiple' => true,
                'expanded' => true,
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
                'choices' => $hosts,
                'choice_attr' => function ($choice, string $key) {
                    if ('Todos' === $key) {
                        return ['checked' => 'checked', 'disabled' => false, 'data-choice-all' => null];
                    }

                    return [];
                },
            ])
        ;
    }
}
