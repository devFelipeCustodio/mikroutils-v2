<?php

namespace App\Form;

use App\Entity\ExportUsers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportUsersFormType extends AbstractType
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
        $builder
            ->add('hosts', ChoiceType::class, [
                'label' => false,
                'multiple' => true,
                'expanded' => true,
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
                'choices' => array_merge(['Todos' => 'all'], $options['hosts']),
            ]);
            // TODO setar opção all como default
    } 
}
