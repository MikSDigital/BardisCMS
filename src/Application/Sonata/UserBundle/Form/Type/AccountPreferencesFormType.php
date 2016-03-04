<?php

/*
 * User Bundle
 * This file is part of the BardisCMS.
 *
 * (c) George Bardis <george@bardis.info>
 *
 */

namespace Application\Sonata\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;

use Application\Sonata\UserBundle\Entity\User;

class AccountPreferencesFormType extends AbstractType {

	private $class;
	private $container;

	/**
     * @param string $class The User class name
     * @param Container $container
	 */
	public function __construct($class, $container) {
		$this->class = $class;
		$this->container = $container;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {

		$user = $this->container->get('security.context')->getToken()->getUser();

		$defaults = array(
            'language' => $user->getLanguage(),
            'currencyCode' => $user->getCurrencyCode(),
            'biography' => $user->getBiography(),
            'website' => $user->getWebsite(),
            'timezone' => $user->getTimezone(),
			'secretQuestion' => $user->getSecretQuestion(),
			'secretQuestionResponse' => $user->getSecretQuestionResponse()
		);

        // Adding custom extra user fields for Account Preferences (including the Security) Form
		$builder
            ->add('language', LanguageType::class, array(
                'preferred_choices' => array(
                    User::LANGUAGE_EN
                ),
                'label' => 'form.language',
                'translation_domain' => 'SonataUserBundle',
                'data' => $defaults['language'],
                'required' => true
            ))
			->add('currencyCode', ChoiceType::class, array(
                'choices' => array(
                    User::CURRENCY_POUND    => 'GBP',
                    User::CURRENCY_EURO     => 'EUR',
                    User::CURRENCY_USD      => 'USD'
                ),
                'preferred_choices' => array(
                    User::CURRENCY_POUND
                ),
                'label' => 'form.currencyCode',
                'translation_domain' => 'SonataUserBundle',
                'data' => $defaults['currencyCode'],
                'required' => true,
                'expanded' => true,
                'multiple' => false
            ))
            ->add('biography', TextareaType::class, array(
                'label' => 'form.biography',
                'translation_domain' => 'SonataUserBundle',
                'data' => $defaults['biography'],
                'required' => false
            ))
            ->add('website', UrlType::class, array(
                'label' => 'form.website',
                'translation_domain' => 'SonataUserBundle',
                'data' => $defaults['website'],
                'required' => false
            ))
            ->add('timezone', TimezoneType::class, array(
                'preferred_choices' => array(
                    User::TIMEZONE_LONDON
                ),
                'label' => 'form.timezone',
                'translation_domain' => 'SonataUserBundle',
                'data' => $defaults['timezone'],
                'required' => false
            ))
            ->add('secretQuestion', ChoiceType::class, array(
                'choices' => array(
                    User::QUESTION_SPOUSE       => 'spouse_name',
                    User::QUESTION_MAIDEN_NAME  => 'maiden_name',
                    User::QUESTION_CAR          => 'first_car',
                    User::QUESTION_PET          => 'first_pet',
                    User::QUESTION_SCHOOL       => 'first_school'
                ),
                'label' => 'form.secretQuestion',
                'translation_domain' => 'SonataUserBundle',
                'data' => $defaults['secretQuestion'],
                'required' => false
            ))
            ->add('secretQuestionResponse', TextType::class, array(
                'label' => 'form.secretQuestionResponse',
                'translation_domain' => 'SonataUserBundle',
                'data' => $defaults['secretQuestionResponse'],
                'required' => false
            ))
        ;
	}

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => $this->class,
            'intention' => 'profile_account_preferences_edit',
        ));
    }

    public function getName() {
        return $this->getBlockPrefix();
    }

    // Define the name of the form to call it for rendering
    public function getBlockPrefix() {
        return 'sonata_user_account_preferences';
    }

    public function getExtendedType()
    {
        return method_exists(AbstractType::class, 'getBlockPrefix') ? FormType::class : 'form';
    }

}
