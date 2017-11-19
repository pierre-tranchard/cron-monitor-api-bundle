<?php

namespace Tranchard\CronMonitorApiBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tranchard\CronMonitorApiBundle\Document\CronReporter;

class CronReporterType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('project', TextType::class);
        $builder->add('job', TextType::class);
        $builder->add('description', TextType::class);
        $builder->add('status', TextType::class);
        $builder->add('duration', IntegerType::class);
        $builder->add('extraPayload');
        $builder->add('environment', TextType::class);
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'      => CronReporter::class,
                'csrf_protection' => false,
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getBlockPrefix()
    {
        return 'tranchard_cron_monitor_api_form_type_cron_reporter';
    }
}
