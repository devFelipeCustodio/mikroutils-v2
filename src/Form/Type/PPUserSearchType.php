<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PPUserSearchType extends AbstractType
{

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "csrf_protection" => false,
            "hosts" => []
        ]);

        $resolver->setAllowedTypes('hosts', 'array');
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('q', SearchType::class, [
                "label" => false,
                "attr" => [
                    "placeholder" => "Digite um nome, IP ou MAC de usuário"
                ]
            ])
            ->add('gw', ChoiceType::class, [
                "label" => false,
                "multiple" => true,
                "expanded" => true,
                'label_attr' => [
                    'class' => 'checkbox-switch'
                ],
                "choices" => $options["hosts"]
            ])
        ;
    }
}